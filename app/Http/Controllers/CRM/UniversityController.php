<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\University;
use Illuminate\Http\Request;

class UniversityController extends Controller
{
    
    public function index($country = NULL)
    {
        $country = str_replace('_', ' ', $country);
        // get all data of university
        // without blank rows
        $universities = University::orderBy('univ_id', 'asc')->where('univ_id', '!=', '');
        if ($country) {
            $universities = $universities->where('univ_country', $country);
        }
        $universities = $universities->get();
        return response()->json(['success' => true, 'data' => $universities]);
    }

    public function country()
    {
        $countries = University::select('univ_country')->groupBy('univ_country')->orderBy('univ_country', 'asc')->where('univ_id', '!=', '')->get();
        $collection = $countries->map(function ($item, $key) {
            return [
                'univ_country' => str_replace(' ', '_', strtolower($item->univ_country))
            ];
        });

        return response()->json([
            'success' => true, 
            'data' => $collection,
        ]);
    }
}
