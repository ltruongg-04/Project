<?php

namespace App\Http\Controllers;

use App\Models\Slide;
use App\Models\Category;
use App\Models\Product;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $slides = Cache::remember('home_slides', 3600, function () {
            return Slide::where('status', 1)->take(3)->get();
        });

        $categories = Cache::remember('home_categories', 3600, function () {
            return Category::orderBy('name')->get();
        });

        $sproducts = Cache::remember('home_sale_products', 600, function () {
            return Product::whereNotNull('sale_price')
                         ->where('sale_price', '<>', '')
                         ->inRandomOrder()
                         ->take(8)
                         ->get();
        });

        $fproducts = Cache::remember('home_featured_products', 600, function () {
            return Product::where('featured', 1)->take(8)->get();
        });

        return view('index', compact('slides', 'categories', 'sproducts', 'fproducts'));
    }

    public function contact()
    {
        return view('contact');
    }

    public function contact_store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100',
            'email' => 'required|email',
            'phone' => 'required|numeric|digits:10',
            'comment' => 'required'
        ]);

        $contact = new Contact();
        $contact->name = $request->name;
        $contact->email = $request->email;
        $contact->phone = $request->phone;
        $contact->comment = $request->comment;
        $contact->save();

        return redirect()->back()->with('success', 'Your message has been sent successfully!');
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $cacheKey = 'search_' . md5($query);

        $results = Cache::remember($cacheKey, 300, function () use ($query) {
            return Product::where('name', 'LIKE', "%{$query}%")->take(8)->get();
        });

        return response()->json($results);
    }
}
