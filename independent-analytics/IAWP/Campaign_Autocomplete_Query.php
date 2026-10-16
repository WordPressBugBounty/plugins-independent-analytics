<?php

namespace IAWP;

use IAWPSCOPED\Illuminate\Database\Query\Builder;
use IAWPSCOPED\Illuminate\Support\Collection;
use InvalidArgumentException;
/** @internal */
class Campaign_Autocomplete_Query
{
    private const LIMIT = 20;
    private const FIELDS = ['path', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'note'];
    public function options(string $query, ?string $field = null) : array
    {
        $field = $field === '' ? null : $field;
        if (\is_string($field) && !\in_array($field, self::FIELDS, \true)) {
            throw new InvalidArgumentException('Invalid campaign autocomplete field');
        }
        $normalized_query = \trim($query);
        $options = Collection::make($this->query_options($normalized_query, $field, self::LIMIT));
        if ($normalized_query === '') {
            return $options->all();
        }
        $is_exact_match = fn(array $option) => $option['field'] === $field && $option['value'] === $query;
        $exact_match = $options->first($is_exact_match);
        // If there is no exact match, add a custom first option containing their typed text
        $first_result = $exact_match ?? ['field' => $field, 'fieldLabel' => \is_string($field) ? $this->field_label($field) : null, 'value' => $query, 'count' => \is_null($field) ? $this->query_all_fields_count($normalized_query) : null];
        return $options->reject($is_exact_match)->prepend($first_result)->take(self::LIMIT)->all();
    }
    private function query_options(string $query, ?string $field, int $limit) : array
    {
        $fields = \is_string($field) ? [$field] : self::FIELDS;
        $field_queries = Collection::make($fields)->map(function (string $field) use($query) {
            $builder = \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->selectRaw('? AS field', [$field])->selectRaw("{$field} AS value")->selectRaw('COUNT(*) AS count')->whereNotNull($field)->whereNot($field, '')->groupBy($field);
            if ($query !== '') {
                $builder->where($field, 'LIKE', $this->like_pattern($query));
            }
            return $builder;
        });
        $combined_query = $field_queries->first();
        $field_queries->skip(1)->each(function (Builder $field_query) use($combined_query) {
            $combined_query->unionAll($field_query);
        });
        $results = \IAWP\Illuminate_Builder::new()->fromSub($combined_query, 'campaign_options')->select(['field', 'value', 'count'])->orderByDesc('count')->orderBy('field')->orderBy('value')->limit($limit)->get()->all();
        return $this->format_options($results);
    }
    private function query_all_fields_count(string $query) : int
    {
        return \IAWP\Illuminate_Builder::new()->from(\IAWP\Tables::campaign_urls())->where(function ($builder) use($query) {
            foreach (self::FIELDS as $index => $field) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $builder->{$method}($field, 'LIKE', $this->like_pattern($query));
            }
        })->count();
    }
    private function format_options(array $results) : array
    {
        return \array_map(function ($result) {
            return ['field' => $result->field, 'fieldLabel' => $this->field_label($result->field), 'value' => $result->value, 'count' => (int) $result->count];
        }, $results);
    }
    private function field_label(string $field) : string
    {
        $labels = ['path' => \__('Path', 'independent-analytics'), 'utm_source' => \__('Source', 'independent-analytics'), 'utm_medium' => \__('Medium', 'independent-analytics'), 'utm_campaign' => \__('Campaign', 'independent-analytics'), 'utm_term' => \__('Term', 'independent-analytics'), 'utm_content' => \__('Content', 'independent-analytics'), 'note' => \__('Note', 'independent-analytics')];
        return $labels[$field];
    }
    private function like_pattern(string $query) : string
    {
        global $wpdb;
        return '%' . $wpdb->esc_like($query) . '%';
    }
}
