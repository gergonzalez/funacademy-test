<?php
/**
 * Transform Provider Data.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\Provider;

class ProviderTransformer extends TransformerAbstract
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
     * Turn Provider object into a generic array.
     *
     * @return array
     */
    public function transform(Provider $provider)
    {
        $data = [
            'companyname' => $provider->company_name,
            'phone' => $provider->phone,
            'IBAN' => $provider->iban,
            'companyDescription' => $provider->company_description,
            'discount' => $provider->discount,
        ];

        return $data;
    }
}
