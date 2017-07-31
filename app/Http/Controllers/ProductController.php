<?php
/**
 * Manage requests to 'products*'.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Transformers\ProductTransformer;
use App\Product;

class ProductController extends Controller
{
    /**
     * Create a new ProductController instance.
     */
    public function __construct()
    {
    }

    /**
     * Display the list of products stored.
     * 
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        return response()->json(app('fractal')->collection(Product::all(), new ProductTransformer())->getArray());
    }
}
