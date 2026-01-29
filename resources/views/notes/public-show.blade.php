<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $note->title }}</title>
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.6; max-width: 720px; margin: 0 auto; padding: 1.5rem; color: #1a1a1a; }
        .note-title { font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem; }
        .note-content { word-wrap: break-word; }
        .note-content p { margin-bottom: 0.75rem; }
        .note-content h1, .note-content h2, .note-content h3 { margin-top: 1rem; margin-bottom: 0.5rem; }
        .note-content ul, .note-content ol { margin: 0.75rem 0; padding-left: 1.5rem; }
    </style>
</head>
<body>
    <h1 class="note-title">{{ $note->title }}</h1>
    <div class="note-content">{!! $note->content !!}</div>
</body>
</html>
