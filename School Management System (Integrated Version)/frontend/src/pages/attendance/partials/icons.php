<?php
declare(strict_types=1);

/**
 * Inline SVG icon set for the attendance workspace.
 */
function attn_icon(string $name, string $class = ''): string
{
    $icons = [
        'overview' => '<path d="M4 19V5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1Z"/><path d="M8 3v3"/><path d="M16 3v3"/><path d="M4 9h16"/><path d="m8.5 14 2 2 4-4"/>',
        'teacher' => '<rect x="3" y="4" width="18" height="12" rx="1.6"/><path d="M8 20h8"/><path d="M12 16v4"/><path d="M8 8h8"/><path d="M8 11h5"/>',
        'student' => '<path d="m3 9 9-5 9 5-9 5-9-5Z"/><path d="M7 11.5V16c0 1.7 2.2 3 5 3s5-1.3 5-3v-4.5"/><path d="M21 9v5"/>',
        'rooms' => '<path d="M4 21V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v17"/><path d="M15 9h4a1 1 0 0 1 1 1v11"/><path d="M2 21h20"/><path d="M8 7h3"/><path d="M8 11h3"/>',
        'conflict' => '<path d="m12 3 9 16H3L12 3Z"/><path d="M12 9v4"/><path d="M12 16h.01"/>',
        'menu' => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'check-circle' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'alert' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
        'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/>',
        'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'door' => '<path d="M4 21V4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v17"/><path d="M2 21h20"/><path d="M15 12h.01"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/>',
        'refresh' => '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'close' => '<path d="m6 6 12 12"/><path d="m18 6-12 12"/>',
        'filter' => '<path d="M3 5h18"/><path d="M6 12h12"/><path d="M10 19h4"/>',
        'building' => '<path d="M4 21V5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v16"/><path d="M14 9h5a1 1 0 0 1 1 1v11"/><path d="M2 21h20"/><path d="M7 8h2"/><path d="M7 12h2"/><path d="M7 16h2"/><path d="M17 13h1"/><path d="M17 17h1"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
        'print' => '<path d="M6 9V4h12v5"/><rect x="4" y="9" width="16" height="8" rx="1.5"/><path d="M7 17h10v4H7z"/>',
        'shield-check' => '<path d="M12 3 20 6v5c0 4.6-3.2 8.4-8 10-4.8-1.6-8-5.4-8-10V6l8-3Z"/><path d="m9 12 2 2 4-4"/>',
    ];

    $path = $icons[$name] ?? $icons['overview'];
    $classAttribute = $class !== '' ? ' class="' . attn_e($class) . '"' : '';

    return '<svg' . $classAttribute . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}
