<?php

namespace App\Http\Controllers;

use App\Support\Seo;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'seo' => Seo::make([
                'canonical' => url('/'),
            ]),
        ]);
    }
}
