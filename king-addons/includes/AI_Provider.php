<?php

/**
 * Central abstraction over the AI providers King Addons can talk to.
 *
 * Every AI feature (text generation, rewriting, translation, alt text, auto
 * tagging, post generation, image generation) goes through here so that adding
 * a provider does not mean touching each call site.
 *
 * Both supported providers speak the OpenAI Chat Completions dialect, so the
 * request/response bodies stay identical — only the base URL, the auth headers
 * and the model list differ.
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

final class AI_Provider
{
    public const OPTION_NAME = 'king_addons_ai_options';

    public const OPENAI = 'openai';
    public const OPENROUTER = 'openrouter';

    /** Legacy cache key, kept so existing installs do not lose their cached list. */
    private const CACHE_OPENAI = 'king_addons_ai_models_cache';
    private const CACHE_OPENROUTER = 'king_addons_ai_openrouter_models_cache';

    private const BASE_OPENAI = 'https://api.openai.com/v1/';
    private const BASE_OPENROUTER = 'https://openrouter.ai/api/v1/';

    /**
     * Fallbacks used when the model list cannot be fetched (no key yet, API down).
     */
    private const FALLBACK_OPENAI = ['gpt-4o-mini' => 'GPT-4o-mini', 'gpt-4.1-nano' => 'GPT-4.1-nano'];
    private const FALLBACK_OPENROUTER = ['openai/gpt-4o-mini' => 'OpenAI: GPT-4o-mini'];

    /**
     * All providers with their human readable labels.
     *
     * @return array<string, string>
     */
    public static function getProviders(): array
    {
        return [
            self::OPENAI => esc_html__('OpenAI', 'king-addons'),
            self::OPENROUTER => esc_html__('OpenRouter', 'king-addons'),
        ];
    }

    /**
     * Normalises an arbitrary value to a supported provider slug.
     */
    public static function normalizeProvider($provider): string
    {
        return ($provider === self::OPENROUTER) ? self::OPENROUTER : self::OPENAI;
    }

    /**
     * The provider currently selected in AI Settings.
     */
    public static function getProvider(): string
    {
        $options = get_option(self::OPTION_NAME, []);
        return self::normalizeProvider(is_array($options) ? ($options['ai_provider'] ?? self::OPENAI) : self::OPENAI);
    }

    public static function isOpenRouter(): bool
    {
        return self::getProvider() === self::OPENROUTER;
    }

    /**
     * Human readable name of a provider.
     */
    public static function getLabel(?string $provider = null): string
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());
        $providers = self::getProviders();
        return $providers[$provider];
    }

    /**
     * API key stored for a provider (defaults to the active one).
     */
    public static function getApiKey(?string $provider = null): string
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());
        $options = get_option(self::OPTION_NAME, []);
        if (!is_array($options)) {
            return '';
        }
        $key = ($provider === self::OPENROUTER)
            ? ($options['openrouter_api_key'] ?? '')
            : ($options['openai_api_key'] ?? '');
        return is_string($key) ? trim($key) : '';
    }

    /**
     * Option key holding a model for the given provider and purpose.
     *
     * @param string $type text|vision|image
     */
    public static function getModelOptionKey(string $type, ?string $provider = null): string
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());
        $prefix = ($provider === self::OPENROUTER) ? 'openrouter' : 'openai';
        switch ($type) {
            case 'vision':
                return $prefix . '_vision_model';
            case 'image':
                return $prefix . '_image_model';
            default:
                return $prefix . '_model';
        }
    }

    /**
     * Default model used when nothing is configured yet.
     *
     * @param string $type text|vision|image
     */
    public static function getDefaultModel(string $type, ?string $provider = null): string
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());
        if ($provider === self::OPENROUTER) {
            return ($type === 'image') ? 'google/gemini-2.5-flash-image' : 'openai/gpt-4o-mini';
        }
        return ($type === 'image') ? 'gpt-image-1' : 'gpt-4o-mini';
    }

    /**
     * Model configured for text generation.
     */
    public static function getTextModel(): string
    {
        return self::getModel('text');
    }

    /**
     * Model configured for image recognition (vision), falling back to the text model.
     */
    public static function getVisionModel(): string
    {
        $model = self::getModel('vision');
        return ($model !== '') ? $model : self::getModel('text');
    }

    /**
     * Model configured for image generation.
     */
    public static function getImageModel(): string
    {
        $model = self::getModel('image');
        return ($model !== '') ? $model : self::getDefaultModel('image');
    }

    /**
     * Reads a stored model for the active provider.
     *
     * @param string $type text|vision|image
     */
    public static function getModel(string $type, ?string $provider = null): string
    {
        $options = get_option(self::OPTION_NAME, []);
        if (!is_array($options)) {
            return '';
        }
        $value = $options[self::getModelOptionKey($type, $provider)] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    /**
     * Base API URL for a provider, with trailing slash.
     */
    public static function getBaseUrl(?string $provider = null): string
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());
        return ($provider === self::OPENROUTER) ? self::BASE_OPENROUTER : self::BASE_OPENAI;
    }

    /**
     * Chat Completions endpoint of a provider.
     */
    public static function getChatEndpoint(?string $provider = null): string
    {
        return self::getBaseUrl($provider) . 'chat/completions';
    }

    /**
     * Image generation endpoint of a provider.
     */
    public static function getImagesEndpoint(?string $provider = null): string
    {
        return self::getBaseUrl($provider) . 'images/generations';
    }

    /**
     * Request headers for a provider. OpenRouter asks integrations to identify
     * themselves so requests are attributed to the site rather than anonymous.
     *
     * @return array<string, string>
     */
    public static function getHeaders(?string $provider = null, ?string $api_key = null): array
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());
        $api_key = ($api_key !== null && $api_key !== '') ? $api_key : self::getApiKey($provider);

        $headers = [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ];

        if ($provider === self::OPENROUTER) {
            $headers['HTTP-Referer'] = home_url('/');
            $headers['X-Title'] = 'King Addons';
        }

        return $headers;
    }

    /**
     * Whether the given URL belongs to one of the AI providers.
     */
    public static function isProviderUrl(string $url): bool
    {
        return strpos($url, 'openai.com') !== false || strpos($url, 'openrouter.ai') !== false;
    }

    /**
     * Link to the provider's usage dashboard.
     */
    public static function getDashboardUrl(?string $provider = null): string
    {
        return (self::normalizeProvider($provider ?? self::getProvider()) === self::OPENROUTER)
            ? 'https://openrouter.ai/activity'
            : 'https://platform.openai.com/usage';
    }

    /**
     * Link to the provider's API key page.
     */
    public static function getApiKeysUrl(?string $provider = null): string
    {
        return (self::normalizeProvider($provider ?? self::getProvider()) === self::OPENROUTER)
            ? 'https://openrouter.ai/keys'
            : 'https://platform.openai.com/api-keys';
    }

    /**
     * Classifies a provider failure so callers can tell a temporary hiccup from
     * a dead end, and report the right thing to the user.
     *
     * Providers answer with very different bodies but fairly consistent status
     * codes, so the status is what the decision is based on.
     *
     * @param \WP_Error|int $error   Error from decodeResponse(), or a raw status code.
     * @param string        $message Message to carry through; taken from the error when omitted.
     * @return array{code: string, retryable: bool, status: int, message: string}
     */
    public static function classifyError($error, string $message = ''): array
    {
        $status = 0;

        if (is_wp_error($error)) {
            $data = $error->get_error_data();
            $status = (int) (is_array($data) ? ($data['status'] ?? 0) : 0);
            if ($message === '') {
                $message = $error->get_error_message();
            }

            // A transport failure never reached the provider, so it carries no
            // status. Timeouts and dropped connections are exactly the kind of
            // hiccup a retry fixes, so they must not fall through to "unknown".
            if ($status === 0 && $error->get_error_code() === 'http_request_failed') {
                return [
                    'code' => 'timeout',
                    'retryable' => true,
                    'status' => 0,
                    'message' => $message,
                ];
            }
        } elseif (is_numeric($error)) {
            $status = (int) $error;
        }

        switch ($status) {
            case 401:
            case 403:
                $code = 'auth';
                $retryable = false;
                break;
            case 402:
                $code = 'credits';
                $retryable = false;
                break;
            case 400:
            case 404:
                // Usually a model id the provider does not serve.
                $code = 'model';
                $retryable = false;
                break;
            case 429:
                // Per-minute throttling and free-tier daily caps both land here.
                $code = 'rate_limit';
                $retryable = true;
                break;
            case 408:
            case 409:
            case 500:
            case 502:
            case 503:
            case 504:
                // The provider or the model behind it is briefly unavailable.
                $code = 'upstream';
                $retryable = true;
                break;
            default:
                $code = 'unknown';
                $retryable = false;
                break;
        }

        // A daily cap is reported as 429 but waiting a few seconds will not
        // clear it, so it must not be retried like ordinary throttling.
        if ($code === 'rate_limit' && preg_match('/\b(daily limit|per day|limit_rpd|quota exceeded|out of credits)\b/i', $message)) {
            $code = 'daily_limit';
            $retryable = false;
        }

        return [
            'code' => $code,
            'retryable' => $retryable,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * HTTP status to answer an AJAX caller with, so the browser sees something
     * closer to the truth than a blanket 500.
     */
    public static function getResponseStatus(array $classified): int
    {
        $status = (int) ($classified['status'] ?? 0);

        // Pass through the statuses a client can act on; collapse the rest to
        // 502, which says "the upstream failed", not "this site broke".
        if (in_array($status, [400, 401, 402, 403, 404, 408, 429], true)) {
            return $status;
        }

        return 502;
    }

    /**
     * Applies provider specific defaults to a Chat Completions payload.
     *
     * King Addons sends deliberately small max_tokens budgets (50 for alt text,
     * 80 for tags). Many models in OpenRouter's catalogue are reasoning models
     * that would spend that entire budget thinking and return an empty message,
     * so reasoning is turned off unless the caller asked for it.
     *
     * @param array $payload Chat Completions payload.
     * @return array
     */
    public static function prepareChatPayload(array $payload, ?string $provider = null): array
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());

        if ($provider === self::OPENROUTER && !isset($payload['reasoning'])) {
            $payload['reasoning'] = ['enabled' => false];
        }

        return $payload;
    }

    /**
     * Reads the assistant message out of a Chat Completions response.
     *
     * @param mixed $data Decoded response body.
     * @return string|\WP_Error Message text, or the reason it is missing.
     */
    public static function extractMessageContent($data)
    {
        if (!is_array($data) || empty($data['choices'][0]) || !is_array($data['choices'][0])) {
            return new \WP_Error('king_addons_ai_empty', self::extractErrorMessage($data));
        }

        $choice = $data['choices'][0];
        $content = $choice['message']['content'] ?? null;

        if (is_string($content) && trim($content) !== '') {
            return trim($content);
        }

        // A model that spent its whole budget before answering needs a clearer
        // message than "unexpected data" — the fix is a different model.
        if (($choice['finish_reason'] ?? '') === 'length') {
            return new \WP_Error('king_addons_ai_truncated', esc_html__('The model ran out of tokens before it produced an answer. Try a model that does not use extended reasoning.', 'king-addons'));
        }

        return new \WP_Error('king_addons_ai_empty', esc_html__('The model returned an empty response.', 'king-addons'));
    }

    /**
     * Turns an error body into one readable line.
     *
     * OpenRouter often answers with a vague "Provider returned error" and puts
     * the real cause in error.metadata, so surface that too.
     *
     * @param mixed  $body     Decoded response body.
     * @param string $fallback Message to use when the body carries nothing useful.
     */
    public static function extractErrorMessage($body, string $fallback = ''): string
    {
        if ($fallback === '') {
            $fallback = esc_html__('The AI provider could not complete the request.', 'king-addons');
        }
        if (!is_array($body) || !isset($body['error'])) {
            return $fallback;
        }

        $error = $body['error'];
        if (!is_array($error)) {
            return is_scalar($error) ? (string) $error : $fallback;
        }

        $message = (isset($error['message']) && is_string($error['message']) && $error['message'] !== '')
            ? $error['message']
            : $fallback;

        $parts = [];
        if (!empty($error['metadata']['provider_name'])) {
            $parts[] = (string) $error['metadata']['provider_name'];
        }
        if (!empty($error['metadata']['raw'])) {
            $raw = $error['metadata']['raw'];
            $parts[] = wp_strip_all_tags(substr(is_string($raw) ? $raw : (string) wp_json_encode($raw), 0, 400));
        }
        if (empty($parts) && !empty($error['code'])) {
            $parts[] = 'code ' . $error['code'];
        }

        return $parts ? $message . ' — ' . implode(': ', $parts) : $message;
    }

    /**
     * A provider can answer HTTP 200 and still carry an error object, so both
     * the status code and the body have to be checked.
     *
     * @param array|\WP_Error $response Raw wp_remote_* response.
     * @return array|\WP_Error Decoded body or an error.
     */
    public static function decodeResponse($response, string $fallback = '')
    {
        if (is_wp_error($response)) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        $body = json_decode($raw, true);

        if ($code < 200 || $code >= 300 || !is_array($body) || isset($body['error'])) {
            $message = is_array($body)
                ? self::extractErrorMessage($body, $fallback)
                : wp_strip_all_tags(substr($raw, 0, 500));

            if (trim($message) === '') {
                /* translators: %d: HTTP status code */
                $message = sprintf(esc_html__('The AI provider returned HTTP %d.', 'king-addons'), $code);
            }

            return new \WP_Error('king_addons_ai_provider', $message, ['status' => $code]);
        }

        return $body;
    }

    /* --------------------------------------------------------------------- */
    /* Model list                                                            */
    /* --------------------------------------------------------------------- */

    /**
     * Transient name holding the cached model list of a provider.
     */
    public static function getCacheKey(?string $provider = null): string
    {
        return (self::normalizeProvider($provider ?? self::getProvider()) === self::OPENROUTER)
            ? self::CACHE_OPENROUTER
            : self::CACHE_OPENAI;
    }

    /**
     * Drops the cached model lists of every provider.
     */
    public static function clearModelsCache(): void
    {
        delete_transient(self::CACHE_OPENAI);
        delete_transient(self::CACHE_OPENROUTER);
    }

    /**
     * Fetches the model catalogue of a provider.
     *
     * @return array<int, array<string, mixed>>|\WP_Error List of model entries.
     */
    public static function fetchModels(?string $provider = null, ?string $api_key = null)
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());
        $api_key = ($api_key !== null && $api_key !== '') ? $api_key : self::getApiKey($provider);

        // OpenRouter serves its catalogue publicly; OpenAI needs the key.
        if ($api_key === '' && $provider !== self::OPENROUTER) {
            return new \WP_Error('missing_key', esc_html__('API key is required to fetch models.', 'king-addons'));
        }

        $args = ['timeout' => 20];
        if ($api_key !== '') {
            $args['headers'] = self::getHeaders($provider, $api_key);
            unset($args['headers']['Content-Type']);
        }

        $body = self::decodeResponse(
            wp_remote_get(self::getBaseUrl($provider) . 'models', $args),
            esc_html__('Invalid response from API.', 'king-addons')
        );

        if (is_wp_error($body)) {
            return $body;
        }

        if (empty($body['data']) || !is_array($body['data'])) {
            return new \WP_Error('no_models', esc_html__('No models found via API.', 'king-addons'));
        }

        $models = ($provider === self::OPENROUTER)
            ? self::parseOpenRouterModels($body['data'])
            : self::parseOpenAiModels($body['data']);

        if (empty($models)) {
            return new \WP_Error('no_models', esc_html__('No models found via API.', 'king-addons'));
        }

        return self::sortModels($models);
    }

    /**
     * OpenAI does not advertise per-model capabilities, so every model stays
     * available for every purpose — same behaviour King Addons always had.
     *
     * @param array $data Raw `data` array from the API.
     * @return array<int, array<string, mixed>>
     */
    private static function parseOpenAiModels(array $data): array
    {
        $models = [];
        foreach ($data as $model) {
            if (!is_array($model) || empty($model['id']) || !is_string($model['id'])) {
                continue;
            }
            $models[] = [
                'id' => $model['id'],
                'label' => $model['id'],
                'free' => false,
                'text' => true,
                'vision' => true,
                'image' => false,
            ];
        }
        return $models;
    }

    /**
     * OpenRouter reports pricing and modalities, so the list can be split into
     * free and paid models and filtered per purpose.
     *
     * @param array $data Raw `data` array from the API.
     * @return array<int, array<string, mixed>>
     */
    private static function parseOpenRouterModels(array $data): array
    {
        $models = [];

        foreach ($data as $model) {
            if (!is_array($model) || empty($model['id']) || !is_string($model['id'])) {
                continue;
            }

            $pricing = [];
            $prices_known = true;
            $raw_pricing = (isset($model['pricing']) && is_array($model['pricing'])) ? $model['pricing'] : [];
            foreach ($raw_pricing as $metric => $price) {
                if (is_numeric($price) && is_finite((float) $price)) {
                    $pricing[$metric] = (float) $price;
                } else {
                    // Nested overrides and non-numeric values mean the price is
                    // not a flat, known number.
                    $prices_known = false;
                }
            }

            // Missing prices and routed prices (-1) are not a promise of free
            // usage. Every advertised charge must be exactly zero.
            $free = $prices_known
                && isset($pricing['prompt'], $pricing['completion'])
                && 0.0 === $pricing['prompt']
                && 0.0 === $pricing['completion'];
            if ($free) {
                foreach ($pricing as $price) {
                    if (0.0 !== $price) {
                        $free = false;
                        break;
                    }
                }
            }

            $architecture = (isset($model['architecture']) && is_array($model['architecture'])) ? $model['architecture'] : [];
            $inputs = isset($architecture['input_modalities']) ? (array) $architecture['input_modalities'] : ['text'];
            $outputs = isset($architecture['output_modalities']) ? (array) $architecture['output_modalities'] : ['text'];

            $models[] = [
                'id' => $model['id'],
                'label' => (isset($model['name']) && is_string($model['name']) && $model['name'] !== '')
                    ? $model['name']
                    : $model['id'],
                'free' => $free,
                'text' => in_array('text', $outputs, true),
                'vision' => in_array('image', $inputs, true),
                'image' => in_array('image', $outputs, true),
                'context' => isset($model['context_length']) ? (int) $model['context_length'] : 0,
            ];
        }

        return $models;
    }

    /**
     * Free models first, then paid ones, each block alphabetical by label.
     *
     * @param array<int, array<string, mixed>> $models
     * @return array<int, array<string, mixed>>
     */
    public static function sortModels(array $models): array
    {
        usort($models, static function ($left, $right) {
            if (!empty($left['free']) !== !empty($right['free'])) {
                return !empty($left['free']) ? -1 : 1;
            }
            $by_label = strcasecmp((string) $left['label'], (string) $right['label']);
            return $by_label !== 0 ? $by_label : strcmp((string) $left['id'], (string) $right['id']);
        });

        return $models;
    }

    /**
     * Model list of a provider, served from cache when available.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getModels(?string $provider = null): array
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());

        $cached = get_transient(self::getCacheKey($provider));
        if (is_array($cached) && !empty($cached)) {
            return self::normalizeCachedModels($cached);
        }

        $fetched = self::fetchModels($provider);
        if (!is_wp_error($fetched)) {
            // OpenAI's list never changes without a key change, so it is kept
            // until refreshed by hand. OpenRouter adds models constantly.
            $ttl = ($provider === self::OPENROUTER) ? DAY_IN_SECONDS : 0;
            set_transient(self::getCacheKey($provider), $fetched, $ttl);
            return $fetched;
        }

        return self::getFallbackModels($provider);
    }

    /**
     * Older installs cached a flat id => label map. Accept both shapes so an
     * upgrade does not need the cache to be cleared first.
     *
     * @param array $cached
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeCachedModels(array $cached): array
    {
        $first = reset($cached);
        if (is_array($first) && isset($first['id'])) {
            return $cached;
        }

        $models = [];
        foreach ($cached as $id => $label) {
            if (!is_string($id) || $id === '') {
                continue;
            }
            $models[] = [
                'id' => $id,
                'label' => is_string($label) ? $label : $id,
                'free' => false,
                'text' => true,
                'vision' => true,
                'image' => false,
            ];
        }
        return $models;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getFallbackModels(?string $provider = null): array
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());
        $fallback = ($provider === self::OPENROUTER) ? self::FALLBACK_OPENROUTER : self::FALLBACK_OPENAI;

        $models = [];
        foreach ($fallback as $id => $label) {
            $models[] = [
                'id' => $id,
                'label' => $label,
                'free' => false,
                'text' => true,
                'vision' => true,
                'image' => false,
            ];
        }
        return $models;
    }

    /**
     * Model list narrowed to a purpose.
     *
     * @param string $type text|vision|image
     * @return array<int, array<string, mixed>>
     */
    public static function getModelsFor(string $type, ?string $provider = null): array
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());
        $models = self::getModels($provider);

        // OpenAI exposes no capability flags; its image models are a fixed pair.
        if ($provider !== self::OPENROUTER) {
            if ($type === 'image') {
                return [
                    ['id' => 'dall-e-3', 'label' => esc_html__('DALL·E 3', 'king-addons'), 'free' => false],
                    ['id' => 'gpt-image-1', 'label' => esc_html__('GPT Image 1', 'king-addons'), 'free' => false],
                ];
            }
            return $models;
        }

        $key = in_array($type, ['vision', 'image'], true) ? $type : 'text';
        $filtered = array_values(array_filter($models, static function ($model) use ($key) {
            return !empty($model[$key]);
        }));

        return !empty($filtered) ? $filtered : $models;
    }

    /**
     * Renders a model <select>, grouping free models above paid ones.
     *
     * @param string $name     Field name attribute.
     * @param string $selected Currently selected model id.
     * @param array  $models   Model entries from getModelsFor().
     * @param array  $attrs    Extra attributes, e.g. ['class' => '...', 'id' => '...'].
     */
    public static function renderModelSelect(string $name, string $selected, array $models, array $attrs = []): void
    {
        $attr_html = '';
        foreach ($attrs as $attr => $value) {
            $attr_html .= sprintf(' %s="%s"', esc_attr($attr), esc_attr($value));
        }

        if (empty($models)) {
            printf('<select name="%s"%s disabled>', esc_attr($name), $attr_html);
            echo '<option value="">' . esc_html__('Could not fetch models. Check the API key?', 'king-addons') . '</option>';
            echo '</select>';
            return;
        }

        $free = array_values(array_filter($models, static function ($model) {
            return !empty($model['free']);
        }));
        $paid = array_values(array_filter($models, static function ($model) {
            return empty($model['free']);
        }));

        // The saved model may have been retired upstream or belong to a list
        // filtered by capability — keep it selectable so saving does not
        // silently switch the user to another model.
        $known = wp_list_pluck($models, 'id');
        $has_selected = ($selected === '' || in_array($selected, $known, true));

        printf('<select name="%s"%s>', esc_attr($name), $attr_html);

        if (!$has_selected) {
            printf(
                '<option value="%s" selected>%s</option>',
                esc_attr($selected),
                esc_html(sprintf(/* translators: %s: model id */ esc_html__('%s (saved)', 'king-addons'), $selected))
            );
        }

        if (!empty($free) && !empty($paid)) {
            echo '<optgroup label="' . esc_attr__('Free models', 'king-addons') . '">';
            self::renderOptions($free, $selected);
            echo '</optgroup>';
            echo '<optgroup label="' . esc_attr__('Paid models', 'king-addons') . '">';
            self::renderOptions($paid, $selected);
            echo '</optgroup>';
        } else {
            self::renderOptions($models, $selected);
        }

        echo '</select>';
    }

    /**
     * @param array<int, array<string, mixed>> $models
     */
    private static function renderOptions(array $models, string $selected): void
    {
        foreach ($models as $model) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($model['id']),
                selected($selected, $model['id'], false),
                esc_html($model['label'])
            );
        }
    }

    /**
     * Verifies an API key against the provider.
     *
     * @return string|\WP_Error Success message, or the reason it failed.
     */
    public static function testConnection(?string $provider = null, ?string $api_key = null)
    {
        $provider = self::normalizeProvider($provider ?? self::getProvider());
        $api_key = ($api_key !== null && $api_key !== '') ? $api_key : self::getApiKey($provider);

        if ($api_key === '') {
            return new \WP_Error('missing_key', esc_html__('Enter an API key first.', 'king-addons'));
        }

        $headers = self::getHeaders($provider, $api_key);
        unset($headers['Content-Type']);

        // OpenRouter has a dedicated key endpoint that also reports credit;
        // OpenAI validates the key on any authenticated call.
        $endpoint = ($provider === self::OPENROUTER)
            ? self::BASE_OPENROUTER . 'key'
            : self::BASE_OPENAI . 'models';

        $body = self::decodeResponse(
            wp_remote_get($endpoint, ['timeout' => 20, 'headers' => $headers]),
            esc_html__('The API key was rejected.', 'king-addons')
        );

        if (is_wp_error($body)) {
            return $body;
        }

        $message = sprintf(
            /* translators: %s: provider name */
            esc_html__('Connected to %s. The API key is valid.', 'king-addons'),
            self::getLabel($provider)
        );

        if ($provider === self::OPENROUTER && isset($body['data']['limit_remaining']) && null !== $body['data']['limit_remaining']) {
            $message .= ' ' . sprintf(
                /* translators: %s: remaining credit, formatted */
                esc_html__('Remaining credit: %s.', 'king-addons'),
                '$' . number_format_i18n((float) $body['data']['limit_remaining'], 2)
            );
        }

        if ($provider === self::OPENAI && !empty($body['data']) && is_array($body['data'])) {
            $message .= ' ' . sprintf(
                /* translators: %d: number of models */
                esc_html__('%d models available.', 'king-addons'),
                count($body['data'])
            );
        }

        return $message;
    }
}
