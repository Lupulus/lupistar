<?php

namespace App\Services;

class ForumContentFormatter
{
    public function toHtml(string $raw): string
    {
        $escaped = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $escaped = preg_replace('/\[color=(#[0-9a-fA-F]{3,6})\](.*?)\[\/color\]/s', '<span class="forum-color" style="color:$1">$2</span>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/__(.+?)__/s', '<u>$1</u>', $escaped) ?? $escaped;
        $escaped = preg_replace('/~~(.+?)~~/s', '<s>$1</s>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\[img\](https?:\/\/[^\s\[\]"]+)\[\/img\]/', '<img class="forum-img" src="$1" alt="Image">', $escaped) ?? $escaped;
        $escaped = preg_replace('/\[yt\]([A-Za-z0-9_-]{6,20})\[\/yt\]/', '<iframe class="forum-yt" width="560" height="315" src="https://www.youtube-nocookie.com/embed/$1" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\[film:(\d+):([^\]]+)\]/', '<a href="#" class="forum-film-link" data-film-id="$1">$2</a>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(^|[^A-Za-z0-9_])@([A-Za-z0-9_]{3,30})\b/', '$1<span class="forum-mention">@${2}</span>', $escaped) ?? $escaped;

        $escaped = nl2br($escaped, false);

        return $escaped;
    }

    public function extractMentions(string $raw): array
    {
        preg_match_all('/(^|[^A-Za-z0-9_])@([A-Za-z0-9_]{3,30})\b/', $raw, $m);
        $names = $m[2] ?? [];
        $names = array_values(array_unique(array_map(static fn ($s) => (string) $s, $names)));

        return $names;
    }
}
