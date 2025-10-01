<?php
declare(strict_types=1);

namespace Services;

class RichTextEditor {
    private string $editorType;
    private array $config;
    
    public function __construct(string $editorType = 'tinymce') {
        $this->editorType = $editorType;
        $this->config = $this->getDefaultConfig();
    }
    
    private function getDefaultConfig(): array {
        return [
            'height' => 400,
            'menubar' => true,
            'plugins' => [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons',
                'codesample', 'math', 'markdown'
            ],
            'toolbar' => 'undo redo | blocks | ' .
                'bold italic forecolor | alignleft aligncenter ' .
                'alignright alignjustify | bullist numlist outdent indent | ' .
                'removeformat | help | code | math | codesample | markdown',
            'content_style' => 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
            'skin' => 'oxide-dark',
            'content_css' => 'dark',
            'automatic_uploads' => true,
            'file_picker_types' => 'image',
            'images_upload_url' => '/api/upload/image',
            'convert_urls' => false,
            'relative_urls' => false,
            'remove_script_host' => false
        ];
    }
    
    public function render(string $name, string $content = '', array $options = []): string {
        $config = array_merge($this->config, $options);
        $configJson = json_encode($config, JSON_HEX_APOS | JSON_HEX_QUOT);
        
        return "
        <textarea name='{$name}' id='{$name}'>{$content}</textarea>
        <script>
        tinymce.init({
            selector: '#{$name}',
            {$configJson}
        });
        </script>";
    }
    
    public function getMarkdownSupport(): string {
        return "
        <script src='https://cdn.jsdelivr.net/npm/marked/marked.min.js'></script>
        <script>
        function convertMarkdown() {
            const markdownText = document.getElementById('markdown-input').value;
            const htmlOutput = marked.parse(markdownText);
            document.getElementById('html-output').innerHTML = htmlOutput;
        }
        </script>";
    }
    
    public function getCodeSyntaxHighlighting(): string {
        return "
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css'>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js'></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js'></script>";
    }
    
    public function getMathFormulaSupport(): string {
        return "
        <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']]
            },
            svg: {
                fontCache: 'global'
            }
        };
        </script>
        <script id='MathJax-script' async src='https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js'></script>";
    }
}