<?php
/**
 * Custom Guard JwtGuard.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use App\Services\Auth\Exceptions\JwtException;

class JwtGuard implements Guard
{
    use GuardHelpers;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Token.
     *
     * @var string
     */
    protected $token;

    /**
     * Payload.
     *
     * @var stdClass
     */
    protected $payload;

    /**
     * Create the new JwtGuard guard.
     *
     * @param \Illuminate\Contracts\Auth\UserProvider $provider
     * @param \Illuminate\Http\Request                $request
     */
    public function __construct(UserProvider $provider, Request $request)
    {
        $this->request = $request;
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \App\User|null
     */
    public function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }
        $token = $this->getTokenForRequest();

        if (!is_null($token) && $this->verifyToken($token)) {
            $payload = $this->getPayload();

            if ($payload->exp > time()) {
                return $this->user = $this->provider->retrieveById($payload->sub);
            }
        }
    }

    /**
     * Attempt to authenticate the user using the given credentials
     * and return the token.
     *
     * @param array $credentials
     * @param bool  $withToken
     *
     * @return bool|string
     */
    public function authentication()
    {
        $token = $this->getTokenForRequest();

        if (!is_null($token) && $this->verifyToken($token)) {
            $payload = $this->getPayload();

            if ($payload->exp < time()) {
                throw new JwtException('Token Expired', 3);
            }

            $user = $this->provider->retrieveById($payload->sub);

            if (is_null($user)) {
                return false;
            }

            return true;
        }
    }

    /**
     * Attempt to authenticate the user using the given credentials
     * and return the token.
     *
     * @param array $credentials
     * @param bool  $withToken
     *
     * @return bool|string
     */
    public function attempt($credentials, $withToken = true)
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            return ($withToken) ? $this->login($user) : true;
        }

        return false;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param mixed $user
     * @param array $credentials
     *
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        return $user !== null && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Set the user and create a new token.
     *
     * @param \Tymon\JWTAuth\Contracts\JWTSubject $user
     *
     * @return string
     */
    public function login($user)
    {
        $this->setUser($user);

        return $this->generateToken($user->id);
    }

    /**
     * Get the token for the current request.
     *
     * @return string
     */
    public function getTokenForRequest()
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $token = $this->request->query('token');

        if (empty($token)) {
            $token = $this->request->input('token');
        }

        if (empty($token)) {
            $token = $this->request->bearerToken();
        }

        return $this->token = $token;
    }

    /**
     * Get the token for the current request.
     *
     * @return string
     */
    public function hasToken()
    {
        return is_null($this->getTokenForRequest()) ? false : true;
    }

    /**
     * Validate a user's credentials.
     *
     * @param array $credentials
     *
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return $this->attempt($credentials, false);
    }

    /**
     * Generate a token for a given user.
     *
     * @param App\User $user
     *
     * @return string
     */
    public function generateToken($userId)
    {
        $header = [
          'typ' => 'JWT',
          'alg' => 'HS256',
        ];

        $payload = [
          'iss' => 'FunAcademyTest',
          'iat' => time(),
          'exp' => time() + config('jwt.ttl') * 60,
          'sub' => $userId,
        ];

        $base64Header = base64_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $base64Payload = base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $headerPayload = $base64Header.'.'.$base64Payload;
        $base64Signature = base64_encode(hash_hmac('sha256', $headerPayload, config('jwt.secret'), true));

        $token = $headerPayload.'.'.$base64Signature;

        return $token;
    }

    /**
     * Refresh the token for a given user.
     *
     * @return string
     */
    public function refreshToken()
    {
        $token = $this->getTokenForRequest();

        if ($this->verifyToken($token)) {
            $payload = $this->getPayload();

            return $this->generateToken($payload->sub);
        }

        return false;
    }

    /**
     * Verify JWT token.
     *
     * @param string $token
     *
     * @return bool
     */
    public function verifyToken($token)
    {
        $parts = explode('.', $token);

        if (count($parts) === 3) {
            $headerPayload = $parts[0].'.'.$parts[1];
            $signature = $parts[2];

            $inputSignature = hash_hmac('sha256', $headerPayload, config('jwt.secret'), true);
            $knownSignature = base64_decode($signature);

            return hash_equals($knownSignature, $inputSignature);
        }

        return false;
    }

    /**
     * Get JWT token payload.
     *
     * @param string $token
     *
     * @return stdClass
     */
    public function getPayload()
    {
        if ($this->payload !== null) {
            return $this->payload;
        }

        if (!$this->token) {
            throw new JwtException('Token Not Provided', 4);
        }

        $parts = explode('.', $this->token);

        return $this->payload = json_decode(base64_decode($parts[1]));
    }
}
