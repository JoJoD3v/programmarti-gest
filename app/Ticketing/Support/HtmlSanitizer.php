<?php

namespace App\Ticketing\Support;

use Mews\Purifier\Facades\Purifier;

/**
 * Email HTML is hostile input: strict whitelist before storing, and remote images stripped again at display time
 * unless the employee asks for them (they are tracking pixels).
 */
class HtmlSanitizer
{
    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        return Purifier::clean($html, [
            'HTML.Allowed' => 'p,br,b,strong,i,em,u,s,blockquote,pre,code,ul,ol,li,a[href|title],img[src|alt|width|height],'
                .'table,thead,tbody,tr,td[colspan|rowspan],th[colspan|rowspan],h1,h2,h3,h4,h5,h6,hr,span,div',
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true],
            'HTML.TargetBlank' => true,
            'HTML.Nofollow' => true,
            'AutoFormat.RemoveEmpty' => false,
            'CSS.AllowedProperties' => [],
            'Attr.AllowedFrameTargets' => ['_blank'],
        ]);
    }

    /**
     * HTML for the sandboxed iframe: no scripts ever run there, remote images only on request.
     */
    public static function forDisplay(?string $sanitized, bool $showImages = false): string
    {
        $html = $sanitized ?? '';

        if (! $showImages) {
            $html = preg_replace('/<img\b[^>]*>/i', '', $html) ?? $html;
        }

        $html = str_ireplace('<a ', '<a rel="noopener noreferrer" target="_blank" ', preg_replace('/\s(rel|target)="[^"]*"/i', '', $html) ?? $html);

        return '<!doctype html><html><head><meta charset="utf-8"><base target="_blank">'
            .'<style>body{font:14px/1.5 system-ui,sans-serif;color:#1e293b;margin:0;word-wrap:break-word}img{max-width:100%;height:auto}blockquote{border-left:3px solid #cbd5e1;margin:0 0 0 4px;padding-left:10px;color:#475569}table{border-collapse:collapse}td,th{padding:2px 6px}</style>'
            .'</head><body>'.$html.'</body></html>';
    }
}
