<?php

namespace IAWP;

/** @internal */
class Campaign_QR_Code_Preferences
{
    private const OPTION_NAME = 'iawp_campaign_qr_code_preferences';
    private const ALLOWED_KEYS = ['size', 'pattern', 'eyes', 'background', 'pattern_color', 'eye_color', 'background_color', 'frame_color', 'frame', 'logo_id'];
    public function get() : array
    {
        $stored = \get_option(self::OPTION_NAME, []);
        if (!\is_array($stored)) {
            return [];
        }
        $preferences = [];
        foreach ($stored as $key => $value) {
            if (!\is_string($key) || !\in_array($key, self::ALLOWED_KEYS, \true)) {
                continue;
            }
            $value = $this->normalize($key, $value);
            if (\is_null($value)) {
                continue;
            }
            $preferences[$key] = $value;
        }
        return $preferences;
    }
    public function update(array $changes) : array
    {
        $preferences = $this->get();
        foreach ($changes as $key => $value) {
            if (!\is_string($key) || !\in_array($key, self::ALLOWED_KEYS, \true)) {
                continue;
            }
            if (\is_null($value)) {
                unset($preferences[$key]);
                continue;
            }
            $value = $this->normalize($key, $value);
            if (\is_null($value)) {
                continue;
            }
            $preferences[$key] = $value;
        }
        if (empty($preferences)) {
            \delete_option(self::OPTION_NAME);
        } else {
            \update_option(self::OPTION_NAME, $preferences, \false);
        }
        return $preferences;
    }
    public function logo(array $preferences) : ?array
    {
        $logo_id = $preferences['logo_id'] ?? null;
        if (!\is_int($logo_id) || !$this->is_valid_logo_id($logo_id)) {
            return null;
        }
        $medium = \wp_get_attachment_image_src($logo_id, 'medium');
        $url = \is_array($medium) && \is_string($medium[0] ?? null) ? $medium[0] : \wp_get_attachment_url($logo_id);
        if (!$this->is_valid_url($url)) {
            return null;
        }
        $file = \get_attached_file($logo_id);
        $name = \is_string($file) && $file !== '' ? \wp_basename($file) : \get_the_title($logo_id);
        return ['id' => $logo_id, 'url' => $url, 'name' => \sanitize_text_field($name)];
    }
    private function normalize(string $key, $value)
    {
        if ($key === 'size') {
            if (!\is_int($value) || !\in_array($value, [256, 512, 1024], \true)) {
                return null;
            }
            return $value;
        }
        if ($key === 'pattern') {
            return $this->normalize_choice($value, ['square', 'rounded', 'dots']);
        }
        if ($key === 'eyes') {
            return $this->normalize_choice($value, ['square', 'rounded', 'circular']);
        }
        if ($key === 'background') {
            return $this->normalize_choice($value, ['white', 'none', 'custom']);
        }
        if ($key === 'frame') {
            return $this->normalize_choice($value, ['none', 'border', 'rounded']);
        }
        if (\in_array($key, ['pattern_color', 'eye_color', 'background_color', 'frame_color'], \true)) {
            if (!\is_string($value) || !\preg_match('/^#[0-9a-f]{6}$/i', $value)) {
                return null;
            }
            return \strtoupper($value);
        }
        if ($key === 'logo_id') {
            if (!\is_int($value) || !$this->is_valid_logo_id($value)) {
                return null;
            }
            return $value;
        }
        return null;
    }
    private function normalize_choice($value, array $allowed) : ?string
    {
        if (!\is_string($value) || !\in_array($value, $allowed, \true)) {
            return null;
        }
        return $value;
    }
    private function is_valid_logo_id(int $logo_id) : bool
    {
        $mime_type = \get_post_mime_type($logo_id);
        $url = \wp_get_attachment_url($logo_id);
        return $logo_id > 0 && \get_post_type($logo_id) === 'attachment' && \is_string($mime_type) && \str_starts_with($mime_type, 'image/') && $this->is_valid_url($url);
    }
    private function is_valid_url($url) : bool
    {
        if (!\is_string($url) || $url === '') {
            return \false;
        }
        return \in_array(\wp_parse_url($url, \PHP_URL_SCHEME), ['http', 'https'], \true);
    }
}
