<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReferenceController extends Controller
{
    public function countries(Request $request): JsonResponse
    {
        $etag = md5('countries');

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        $countries = Country::orderBy('name_' . ($request->header('Accept-Language') === 'mk' ? 'mk' : 'en'))->get(['iso2', 'name_mk', 'name_en']);

        return response()->json(['countries' => $countries])->header('ETag', $etag);
    }

    public function currencies(Request $request): JsonResponse
    {
        $etag = md5('currencies');

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        $currencies = Currency::orderBy('code')->get(['code', 'name_mk', 'name_en', 'minor_units']);

        return response()->json(['currencies' => $currencies])->header('ETag', $etag);
    }

    public function locales(Request $request): JsonResponse
    {
        $etag = md5('locales');

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        $locales = Locale::orderByDesc('is_default')->get(['code', 'name', 'is_default']);

        return response()->json(['locales' => $locales])->header('ETag', $etag);
    }
}