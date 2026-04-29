<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
class DeveloperDomainController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'domain' => 'required|string'
        ]);

        $developer = auth()->user()->effectiveDeveloper();

        $domains = $developer->allowed_domains ?? [];

        $domain = rtrim($request->domain, '/');

        if (!in_array($domain, $domains)) {
            $domains[] = $domain;
        }

        $developer->allowed_domains = $domains;
        $developer->save();

        return back()->with('success', 'Domain added');
    }


    public function delete(Request $request)
    {
        $developer = auth()->user()->effectiveDeveloper();

        $domains = $developer->allowed_domains ?? [];

        $domains = array_filter(
            $domains,
            fn ($d) => $d !== $request->domain
        );

        $developer->allowed_domains = array_values($domains);
        $developer->save();

        return back()->with('success', 'Domain removed');
    }
}
