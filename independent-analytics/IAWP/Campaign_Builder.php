<?php

namespace IAWP;

use IAWPSCOPED\Carbon\CarbonImmutable;
use IAWP\Utils\CSV;
use IAWP\Utils\Format;
use IAWP\Utils\Singleton;
use IAWP\Utils\URL;
use IAWPSCOPED\Illuminate\Database\Query\Builder;
use IAWPSCOPED\Illuminate\Support\Carbon;
use IAWPSCOPED\Illuminate\Support\Collection;
use IAWPSCOPED\Illuminate\Support\Str;
use InvalidArgumentException;
use IAWPSCOPED\League\Uri\Uri;
/** @internal */
class Campaign_Builder
{
    use Singleton;
    private const FIELDS = ['path', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'note'];
    public function __construct()
    {
    }
    public function render_campaign_builder()
    {
        echo \IAWPSCOPED\iawp_render('campaign-builder.index', ['campaigns' => $this->get_previously_created_campaigns()]);
    }
    public function render_campaign_list(array $filters) : array
    {
        $filters = $this->normalize_filters($filters);
        $campaigns = $this->get_previously_created_campaigns($filters);
        $total_campaign_count = \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->count();
        return ['html' => \IAWPSCOPED\iawp_render('campaign-builder.list', ['campaigns' => $campaigns, 'has_campaigns' => $total_campaign_count > 0, 'has_filters' => \count($filters) > 0]), 'count' => \count($campaigns), 'total_count' => $total_campaign_count];
    }
    public function campaign_urls_csv(array $filters) : CSV
    {
        $filters = $this->normalize_filters($filters);
        $rows = $this->get_campaign_url_records($filters)->map(function ($row) {
            $date = Carbon::parse($row->created_at, 'utc');
            return [$date->format(Format::date()), $this->as_url($row), Str::start($row->path, '/'), $row->utm_source, $row->utm_medium, $row->utm_campaign, $row->utm_term ?? '', $row->utm_content ?? '', $row->note ?? ''];
        })->all();
        return new CSV([\__('Created At', 'independent-analytics'), \__('URL', 'independent-analytics'), \__('Landing Page Path', 'independent-analytics'), \__('Source', 'independent-analytics'), \__('Medium', 'independent-analytics'), \__('Campaign', 'independent-analytics'), \__('Term', 'independent-analytics'), \__('Content', 'independent-analytics'), \__('Private Note', 'independent-analytics')], $rows);
    }
    public function create_campaign(string $path, string $source, string $medium, string $campaign, string $term, string $content, string $note) : array
    {
        $fields = $this->normalize_campaign_fields($path, $source, $medium, $campaign, $term, $content, $note);
        $errors = $this->validate_campaign_fields($fields);
        if (\count($errors) > 0) {
            return ['errors' => $errors];
        }
        $campaign_url_id = \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->insertGetId(\array_merge($fields, ['created_at' => CarbonImmutable::now('utc')->format('Y-m-d H:i:s')]));
        $campaign_url_record = \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->where('campaign_url_id', '=', $campaign_url_id)->first();
        return ['campaign_url_id' => (int) $campaign_url_id, 'url' => $this->as_url($campaign_url_record), 'errors' => []];
    }
    public function update_campaign(int $campaign_url_id, string $path, string $source, string $medium, string $campaign, string $term, string $content, string $note) : ?array
    {
        $campaign_url_record = \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->where('campaign_url_id', '=', $campaign_url_id)->first();
        if (\is_null($campaign_url_record)) {
            return null;
        }
        $fields = $this->normalize_campaign_fields($path, $source, $medium, $campaign, $term, $content, $note);
        $errors = $this->validate_campaign_fields($fields);
        if (\count($errors) > 0) {
            return ['errors' => $errors];
        }
        \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->where('campaign_url_id', '=', $campaign_url_id)->update($fields);
        $campaign_url_record = \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->where('campaign_url_id', '=', $campaign_url_id)->first();
        return ['campaign_url_id' => $campaign_url_id, 'url' => $this->as_url($campaign_url_record), 'errors' => []];
    }
    private function normalize_campaign_fields(string $path, string $source, string $medium, string $campaign, string $term, string $content, string $note) : array
    {
        $path = \trim($path);
        $term = \trim($term);
        $content = \trim($content);
        $note = \trim($note);
        return ['path' => \str_starts_with($path, '/') ? \substr($path, 1) : $path, 'utm_source' => \trim($source), 'utm_medium' => \trim($medium), 'utm_campaign' => \trim($campaign), 'utm_term' => $term === '' ? null : $term, 'utm_content' => $content === '' ? null : $content, 'note' => $note === '' ? null : $note];
    }
    private function validate_campaign_fields(array $fields) : array
    {
        $errors = [];
        $is_path_a_url = (new URL($fields['path']))->is_valid_url();
        $url = new URL(\trailingslashit(\home_url()) . $fields['path']);
        if ($is_path_a_url) {
            $errors['path'] = \esc_html__('Path should not be a full URL', 'independent-analytics');
        } elseif (!$url->is_valid_url()) {
            $errors['path'] = \esc_html__('Path invalid', 'independent-analytics');
        }
        if ($fields['utm_source'] === '') {
            $errors['utm_source'] = \esc_html__('Source is required', 'independent-analytics');
        }
        if ($fields['utm_medium'] === '') {
            $errors['utm_medium'] = \esc_html__('Medium is required', 'independent-analytics');
        }
        if ($fields['utm_campaign'] === '') {
            $errors['utm_campaign'] = \esc_html__('Campaign is required', 'independent-analytics');
        }
        return $errors;
    }
    private function get_previously_created_campaigns(array $filters = []) : array
    {
        return $this->get_campaign_url_records($filters)->map(function ($row) {
            $date = Carbon::parse($row->created_at, 'utc');
            return ['campaign_url_id' => $row->campaign_url_id, 'created_at' => $date->format(Format::date()), 'created_ago' => $date->diffForHumans(), 'url' => $this->as_url($row), 'note' => $row->note ?? '', 'editor_fields' => ['path' => $row->path ?? '', 'utm_source' => $row->utm_source, 'utm_medium' => $row->utm_medium, 'utm_campaign' => $row->utm_campaign, 'utm_term' => $row->utm_term ?? '', 'utm_content' => $row->utm_content ?? '', 'note' => $row->note ?? '']];
        })->all();
    }
    private function get_campaign_url_records(array $filters) : Collection
    {
        $query = \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->select('*')->orderByDesc('created_at')->orderByDesc('campaign_url_id');
        foreach ($filters as $filter) {
            // Is the filter applied to an individual field or searching across all fields?
            if (\is_string($filter['field'])) {
                $query->where($filter['field'], '=', $filter['value']);
            } else {
                $query->where(function (Builder $query) use($filter) {
                    foreach (self::FIELDS as $index => $field) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $query->{$method}($field, 'LIKE', $this->like_pattern($filter['value']));
                    }
                });
            }
        }
        return $query->get();
    }
    private function normalize_filters(array $filters) : array
    {
        return Collection::make($filters)->map(function ($filter) {
            if (!\is_array($filter) || !\array_key_exists('field', $filter) || !\array_key_exists('value', $filter) || !\is_null($filter['field']) && !\is_string($filter['field']) || !\is_string($filter['value'])) {
                throw new InvalidArgumentException('Invalid campaign filter');
            }
            $field = \is_string($filter['field']) ? \trim($filter['field']) : null;
            $field = $field === '' ? null : $field;
            $value = \trim($filter['value']);
            if (\is_string($field) && !\in_array($field, self::FIELDS, \true)) {
                throw new InvalidArgumentException('Invalid campaign filter field');
            }
            return ['field' => $field, 'value' => $value];
        })->reject(fn(array $filter) => $filter['value'] === '')->values()->all();
    }
    private function like_pattern(string $query) : string
    {
        global $wpdb;
        return '%' . $wpdb->esc_like($query) . '%';
    }
    private function as_url(object $row) : string
    {
        $path = \str_starts_with($row->path, '/') ? \substr($row->path, 1) : $row->path;
        $uri = Uri::createFromString(\trailingslashit(\home_url()) . $path);
        $existing_query = $uri->getQuery();
        if (\is_null($existing_query)) {
            $existing_query = [];
        } else {
            \parse_str($existing_query, $existing_query);
        }
        $existing_query['utm_source'] = $row->utm_source;
        $existing_query['utm_medium'] = $row->utm_medium;
        $existing_query['utm_campaign'] = $row->utm_campaign;
        if (isset($row->utm_term)) {
            $existing_query['utm_term'] = $row->utm_term;
        }
        if (isset($row->utm_content)) {
            $existing_query['utm_content'] = $row->utm_content;
        }
        return $uri->withQuery(\http_build_query($existing_query))->toString();
    }
    public static function has_campaigns() : bool
    {
        return \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->exists();
    }
    public static function delete_campaign(int $campaign_url_id) : bool
    {
        $deleted_rows = \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->where('campaign_url_id', '=', $campaign_url_id)->delete();
        return $deleted_rows > 0;
    }
    public static function duplicate_campaign(int $campaign_url_id) : ?int
    {
        $campaign = \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->where('campaign_url_id', '=', $campaign_url_id)->first();
        if (\is_null($campaign)) {
            return null;
        }
        $new_campaign_url_id = \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->insertGetId(['path' => $campaign->path, 'utm_source' => $campaign->utm_source, 'utm_medium' => $campaign->utm_medium, 'utm_campaign' => $campaign->utm_campaign, 'utm_term' => $campaign->utm_term, 'utm_content' => $campaign->utm_content, 'note' => $campaign->note, 'created_at' => CarbonImmutable::now('utc')->format('Y-m-d H:i:s')]);
        return (int) $new_campaign_url_id;
    }
}
