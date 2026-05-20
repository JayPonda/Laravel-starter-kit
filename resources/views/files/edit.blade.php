@extends('layouts.app')

@section('title', 'Edit: ' . $file->original_name)

@php
$modeMap = [
    'js' => 'javascript',
    'ts' => 'typescript',
    'jsx' => 'jsx',
    'tsx' => 'tsx',
    'py' => 'python',
    'rb' => 'ruby',
    'php' => 'php',
    'java' => 'java',
    'c' => 'c_cpp',
    'cpp' => 'c_cpp',
    'h' => 'c_cpp',
    'hpp' => 'c_cpp',
    'cs' => 'csharp',
    'go' => 'golang',
    'rs' => 'rust',
    'swift' => 'swift',
    'kt' => 'kotlin',
    'scala' => 'scala',
    'html' => 'html',
    'css' => 'css',
    'scss' => 'scss',
    'less' => 'less',
    'json' => 'json',
    'xml' => 'xml',
    'yaml' => 'yaml',
    'yml' => 'yaml',
    'md' => 'markdown',
    'sql' => 'sql',
    'sh' => 'sh',
    'bash' => 'bash',
    'zsh' => 'bash',
    'ps1' => 'powershell',
    'vue' => 'vue',
    'svelte' => 'html',
];

$mode = $modeMap[$extension] ?? 'text';
@endphp

@section('content')
<div class="editor-page">
    <div class="editor-header flex-between-center">
        <div class="flex-center gap-2">
            <a href="{{ route('web.files.index') }}" class="btn btn-secondary btn-small">← Back</a>
            <h2 class="m-0">{{ $file->original_name }}</h2>
        </div>
        <button type="button" class="btn btn-primary" onclick="saveFile()">
            Save
        </button>
    </div>

    <form id="editorForm" method="POST" action="{{ route('web.files.update', $file) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="content" id="fileContent">
    </form>

    <div id="editor">{{ $content }}</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.min.js"></script>
<script>
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");
    editor.getSession().setMode("ace/mode/{{ $mode }}");
    editor.getSession().setUseWorker(false);
    editor.setShowPrintMargin(false);
    editor.setFontSize(14);

    function saveFile() {
        document.getElementById('fileContent').value = editor.getValue();
        document.getElementById('editorForm').submit();
    }
</script>

<style>
    .editor-page {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 120px);
    }

    .editor-header {
        padding: 1rem;
        background: #1e1e1e;
        border-radius: 8px 8px 0 0;
    }

    #editor {
        flex: 1;
        min-height: 400px;
        border-radius: 0 0 8px 8px;
        font-size: 14px;
    }
</style>
@endsection