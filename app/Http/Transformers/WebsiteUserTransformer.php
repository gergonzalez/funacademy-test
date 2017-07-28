<?php
/**
 * Transform Website User Data.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\WebsiteUser;

class WebsiteUserTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include.
     *
     * @var array
     */
    protected $availableIncludes = [
    ];

    /**
     * List of resources included.
     *
     * @var array
     */
    protected $defaultIncludes = [
    ];

    /**
     * Turn WebsiteUser object into a generic array.
     *
     * @return array
     */
    public function transform(WebsiteUser $websiteUser)
    {
        return [
            'name' => $websiteUser->name,
            'mobilePhone' => $websiteUser->mobile,
        ];
    }
}
