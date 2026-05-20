@extends('layouts.app')

@section('title', 'Edit: ' . $file->original_name)

@section('container_class', 'flex-main')

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
<div class="editor-wrapper">
    <div class="editor-toolbar">
        <div class="flex-center gap-4">
            <a href="{{ route('web.files.index') }}" class="btn btn-secondary btn-small">← Exit</a>
            <div class="file-info">
                <span class="file-name">{{ $file->original_name }}</span>
                <span class="file-mode status-badge status-loading">{{ strtoupper($mode) }}</span>
            </div>
        </div>
        <div class="flex-center gap-2">
            <span id="saveStatus" class="text-small mr-2" style="color: #888;"></span>
            <button type="button" class="btn btn-primary" onclick="saveFile()">
                Save Changes
            </button>
        </div>
    </div>

    <form id="editorForm" method="POST" action="{{ route('web.files.update', $file) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="content" id="fileContent">
    </form>

    <div id="editor-container">
        <div id="editor">{{ $content }}</div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ext-language_tools.min.js"></script>
<script>
    // Configure Ace to load dependencies from the CDN
    ace.config.set('basePath', 'https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/');

    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");
    editor.getSession().setMode("ace/mode/{{ $mode }}");
    editor.getSession().setUseWorker(false);
    editor.setShowPrintMargin(false);
    editor.setFontSize(14);
    
    // Enable autocompletion (requires ext-language_tools.min.js)
    editor.setOptions({
        enableBasicAutocompletion: true,
        enableLiveAutocompletion: true,
        useSoftTabs: true,
        tabSize: 4
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        editor.resize();
    });

    function saveFile() {
        const status = document.getElementById('saveStatus');
        status.textContent = 'Saving...';
        
        document.getElementById('fileContent').value = editor.getValue();
        document.getElementById('editorForm').submit();
    }
</script>

<style>
    .flex-main {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 120px);
    }

    .editor-wrapper {
        display: flex;
        flex-direction: column;
        flex: 1;
        background: #272822;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: 1px solid #ddd;
    }

    .editor-toolbar {
        height: 54px;
        background: #f8f9fa; /* Lighter toolbar to match style guide better */
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 1.5rem;
        border-bottom: 1px solid #ddd;
        flex-shrink: 0;
    }

    .file-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .file-name {
        color: #1c1e21;
        font-weight: 600;
        font-size: 14px;
    }

    .file-mode {
        font-size: 10px;
        padding: 2px 6px;
        margin-bottom: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    #editor-container {
        flex: 1;
        position: relative;
        overflow: hidden;
    }

    #editor {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
    }

    .gap-2 { gap: 0.5rem; }
    .gap-4 { gap: 1rem; }
</style>
@endsection