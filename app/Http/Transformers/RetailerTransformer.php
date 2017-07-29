<?php
/**
 * Transform Retailer Data.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\Retailer;

class RetailerTransformer extends TransformerAbstract
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
    'providers',
    ];

    /**
     * Turn Retailer object into a generic array.
     *
     * @return array
     */
    public function transform(Retailer $retailer)
    {
        return [
            'name' => $retailer->name,
            'retailerResponsibleName' => $retailer->responsible_name,
            'phone' => $retailer->phone,
            'mobilePhone' => $retailer->mobile,
            'address' => $retailer->address,
            'IBAN' => $retailer->iban,
        ];
    }

    /**
     * Include Providers.
     *
     * @return League\Fractal\CollectionResource
     */
    public function includeProviders(Retailer $retailer)
    {
        return $this->collection($retailer->providers()->get(), new ExtendedProviderTransformer());
    }
}
