<?php

namespace Modules\Cms\Services;

use Modules\Cms\Models\Content;

class ContainerRenderer
{
    /**
     * Render all container sections for a content entry.
     *
     * Meta is stored keyed by field name (e.g. `meta['page_builder']`). Each
     * container item carries a `_layout` (or `_type`) key naming the section
     * partial to render, e.g. 'hero', 'cta', 'services'.
     */
    public static function render(Content $entry, ?string $containerFieldName = null): string
    {
        $meta = $entry->getAllMeta();

        if ($containerFieldName !== null) {
            $value = $meta[$containerFieldName] ?? null;

            return is_array($value) ? static::renderItems($value) : '';
        }

        $html = '';

        foreach ($meta as $fieldName => $value) {
            if (str_starts_with($fieldName, '_') || ! is_array($value)) {
                continue;
            }

            // Only iterate arrays whose items look like layout blocks.
            if (static::looksLikeLayoutBlocks($value)) {
                $html .= static::renderItems($value);
            }
        }

        return $html;
    }

    /**
     * Render a list of container items (each must carry `_layout`/`_type`).
     */
    public static function renderItems(array $items): string
    {
        $html = '';

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $layoutName = $item['_layout'] ?? $item['_type'] ?? null;
            if (! $layoutName) {
                continue;
            }

            $html .= static::renderSection($layoutName, $item, $index);
        }

        return $html;
    }

    /**
     * Render a single section by layout name.
     */
    public static function renderSection(string $layoutName, array $data, int $index = 0): string
    {
        $viewName = 'cms::frontend.sections.'.$layoutName;

        if (! view()->exists($viewName)) {
            $viewName = 'cms::frontend.sections._default';
        }

        try {
            return view($viewName, [
                'data' => $data,
                'layout' => $data['_layout'] ?? $layoutName,
                'fields' => [],
                'index' => $index,
            ])->render();
        } catch (\Throwable $e) {
            return '<!-- Section render error: '.e($e->getMessage()).' -->';
        }
    }

    /**
     * True when the array is a list of layout blocks (has `_layout`/`_type`).
     */
    protected static function looksLikeLayoutBlocks(array $items): bool
    {
        foreach ($items as $item) {
            if (is_array($item) && (isset($item['_layout']) || isset($item['_type']))) {
                return true;
            }
        }

        return false;
    }
}