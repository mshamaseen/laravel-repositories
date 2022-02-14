<?php

namespace Shamaseen\Repository\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class MultiLanguage
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $preferred = Cookie::get('language');

        if (!$preferred) {
            $configLocale = config('app.locale');

            $browserLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE']
                ? $_SERVER['HTTP_ACCEPT_LANGUAGE']
                : $configLocale;
            $preferred = $this->preferredLanguage(config('app.locales', [$configLocale]), $browserLanguage);

            Cookie::queue(Cookie::forever('language', $preferred));
        }

        \App::setLocale($preferred);

        return $next($request);
    }

    /**
     * Detect the preferred language form the user browser setting.
     *
     * @param $http_accept_language
     *
     * @return int|string
     */
    public function preferredLanguage(array $available_languages, $http_accept_language)
    {
        $available_languages = array_flip($available_languages);

        $langs = [];
        preg_match_all('~([\w-]+)(?:[^,\d]+([\d.]+))?~', strtolower($http_accept_language), $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            list($a, $b) = explode('-', $match[1]) + ['', ''];

            $value = isset($match[2]) ? (float) $match[2] : 1.0;

            if (isset($available_languages[$match[1]])) {
                $langs[$match[1]] = max($value, $langs[$match[1]] ?? 0);
                continue;
            }

            if (isset($available_languages[$a])) {
                $langs[$a] = max($value, $langs[$a] ?? 0);
            }
        }
        arsort($langs);

        return array_keys($langs)[0] ?? 'en';
    }
}
