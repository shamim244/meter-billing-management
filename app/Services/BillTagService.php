<?php

namespace App\Services;

use App\Models\SystemSetting;

class BillTagService
{
    /**
     * Default pre-configured tags if not customized in SystemSetting.
     */
    protected const DEFAULT_TAGS = [
        [
            'code' => 'OK',
            'label' => 'OK',
            'short_label' => 'OK',
            'color' => 'emerald',
            'is_default' => true,
            'is_active' => true,
            'order' => 1,
        ],
        [
            'code' => 'BQC',
            'label' => 'BQC',
            'short_label' => 'BQC',
            'color' => 'blue',
            'is_default' => false,
            'is_active' => true,
            'order' => 2,
        ],
        [
            'code' => 'RCQ',
            'label' => 'RCQ',
            'short_label' => 'RCQ',
            'color' => 'purple',
            'is_default' => false,
            'is_active' => true,
            'order' => 3,
        ],
        [
            'code' => '24days',
            'label' => '24 Days',
            'short_label' => '24 Days',
            'color' => 'amber',
            'is_default' => false,
            'is_active' => true,
            'order' => 4,
        ],
        [
            'code' => 'NOT_APPROVED_PREV_BQC_RQC',
            'label' => 'Not-approved Previous BQC and RQC',
            'short_label' => 'Not-Apprv Prev BQC/RQC',
            'color' => 'rose',
            'is_default' => false,
            'is_active' => true,
            'order' => 5,
        ],
    ];

    /**
     * Get all configured tags (including inactive for admin management).
     */
    public function getAllTags(): array
    {
        $stored = SystemSetting::get('bill_tags_config', null);
        if ($stored && is_array($stored) && count($stored) > 0) {
            return $stored;
        }

        return self::DEFAULT_TAGS;
    }

    /**
     * Get active tags sorted by order for UI and review cards.
     */
    public function getActiveTags(): array
    {
        $tags = $this->getAllTags();
        $active = array_values(array_filter($tags, fn($t) => !empty($t['is_active'])));

        usort($active, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        return $active;
    }

    /**
     * Get default tag code (e.g. 'OK').
     */
    public function getDefaultTag(): string
    {
        $tags = $this->getActiveTags();
        foreach ($tags as $tag) {
            if (!empty($tag['is_default'])) {
                return (string) $tag['code'];
            }
        }

        return 'OK';
    }

    /**
     * Save/update tags configuration in SystemSetting.
     */
    public function saveTagConfig(array $tags): void
    {
        // Ensure at least one default tag
        $hasDefault = false;
        foreach ($tags as &$t) {
            $t['code'] = trim((string)($t['code'] ?? ''));
            $t['label'] = trim((string)($t['label'] ?? $t['code']));
            $t['short_label'] = trim((string)($t['short_label'] ?? $t['label']));
            $t['color'] = trim((string)($t['color'] ?? 'slate'));
            $t['is_active'] = !empty($t['is_active']);
            $t['order'] = (int)($t['order'] ?? 1);

            if (!empty($t['is_default']) && !$hasDefault && $t['is_active']) {
                $hasDefault = true;
                $t['is_default'] = true;
            } else {
                $t['is_default'] = false;
            }
        }

        if (!$hasDefault && count($tags) > 0) {
            $tags[0]['is_default'] = true;
        }

        SystemSetting::set('bill_tags_config', array_values($tags));
    }

    /**
     * Find tag definition by code.
     */
    public function getTagByCode(?string $code): ?array
    {
        if (empty($code)) {
            $code = 'OK';
        }

        $cleanCode = trim(strtoupper($code));
        foreach ($this->getAllTags() as $tag) {
            if (strtoupper($tag['code']) === $cleanCode || strtoupper($tag['label']) === $cleanCode) {
                return $tag;
            }
        }

        return null;
    }

    /**
     * Get short display label for a tag code.
     */
    public function getDisplayLabel(?string $code): string
    {
        $tag = $this->getTagByCode($code);
        return $tag['short_label'] ?? ($tag['label'] ?? ($code ?: 'OK'));
    }

    /**
     * Get full descriptive label for export/tooltip.
     */
    public function getFullLabel(?string $code): string
    {
        $tag = $this->getTagByCode($code);
        return $tag['label'] ?? ($code ?: 'OK');
    }

    /**
     * Delete a tag by code from configuration.
     */
    public function deleteTag(string $code): bool
    {
        $tags = $this->getAllTags();
        $cleanCode = strtoupper(trim($code));
        $filtered = [];
        $deleted = false;

        foreach ($tags as $t) {
            if (strtoupper($t['code']) === $cleanCode) {
                $deleted = true;
                continue;
            }
            $filtered[] = $t;
        }

        if ($deleted) {
            $this->saveTagConfig($filtered);
            return true;
        }

        return false;
    }

    /**
     * Reset tags configuration to factory defaults.
     */
    public function resetToFactory(): void
    {
        SystemSetting::set('bill_tags_config', self::DEFAULT_TAGS);
    }
}
