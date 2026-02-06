/* global KingAddonsFacetedFilters */

const KAFacetedFilters = (() => {
  const contexts = {};
  const debounceTimers = {};

  const slugify = (value) =>
    encodeURIComponent(String(value ?? '').trim().toLowerCase().replace(/\s+/g, '-'));
  const decodeSlug = (value) => decodeURIComponent(value || '');

  const getDefaults = () => ({
    queryId: '',
    widgetId: '',
    postId: '',
    page: 1,
    filters: {
      taxonomy: {},
      meta: {},
      price: {},
      search: '',
      orderby: '',
      order: '',
    },
    persistUrl: false,
    counts: {},
  });

  const findGrids = () =>
    Array.from(document.querySelectorAll('[data-ka-filters="1"]'));

  const findFilters = () =>
    Array.from(document.querySelectorAll('[data-ka-filter-type]'));

  const debounce = (key, fn, delay = 300) => {
    clearTimeout(debounceTimers[key]);
    debounceTimers[key] = setTimeout(fn, delay);
  };

  const ensureContext = (element) => {
    const dataset = element.dataset;
    const queryId = dataset.kaQueryId || '';
    if (!queryId) {
      return null;
    }

    if (!contexts[queryId]) {
      contexts[queryId] = {
        element,
        paginationEl: null,
        state: {
          ...getDefaults(),
          queryId,
          widgetId: dataset.kaWidgetId || '',
          postId: dataset.kaPostId || '',
          persistUrl: dataset.kaPersistUrl === '1',
        },
        loading: false,
      };
      hydrateStateFromUrl(contexts[queryId]);
      syncFiltersUI(contexts[queryId]);
    } else {
      contexts[queryId].element = element;
    }

    return contexts[queryId];
  };

  const buildPayload = (context) => ({
    query_id: context.state.queryId,
    widget_id: context.state.widgetId,
    post_id: context.state.postId,
    page: context.state.page,
    filters: context.state.filters,
  });

  const updateUrl = (context) => {
    if (!context.state.persistUrl) {
      return;
    }

    const params = new URLSearchParams(window.location.search);
    const prefix = `ka_${context.state.queryId}_`;

    [...params.keys()].forEach((key) => {
      if (key.startsWith(prefix)) {
        params.delete(key);
      }
    });

    Object.entries(context.state.filters.taxonomy || {}).forEach(
      ([taxonomy, terms]) => {
        if (terms && terms.length) {
          params.set(`${prefix}tax_${taxonomy}`, terms.join(','));
        }
      }
    );

    if (context.state.filters.search) {
      params.set(`${prefix}search`, context.state.filters.search);
    }

    if (
      context.state.filters.price &&
      (context.state.filters.price.min || context.state.filters.price.max)
    ) {
      if (context.state.filters.price.min !== undefined) {
        params.set(`${prefix}price_min`, context.state.filters.price.min);
      }
      if (context.state.filters.price.max !== undefined) {
        params.set(`${prefix}price_max`, context.state.filters.price.max);
      }
      if (context.state.filters.price.bucket !== undefined) {
        params.set(`${prefix}price_bucket`, context.state.filters.price.bucket);
      }
    }

    if (context.state.page && context.state.page !== 1) {
      params.set(`${prefix}page`, context.state.page);
    }

    if (context.state.filters.orderby) {
      params.set(`${prefix}orderby`, context.state.filters.orderby);
    }

    if (context.state.filters.order) {
      params.set(`${prefix}order`, context.state.filters.order);
    }

    Object.entries(context.state.filters.meta || {}).forEach(([metaKey, metaVal]) => {
      if (Array.isArray(metaVal)) {
        if (metaVal.length) {
          params.set(`${prefix}meta_${metaKey}`, metaVal.join(','));
        }
      } else if (typeof metaVal === 'object') {
        if (metaVal.min !== undefined) {
          params.set(`${prefix}meta_${metaKey}_min`, metaVal.min);
        }
        if (metaVal.max !== undefined) {
          params.set(`${prefix}meta_${metaKey}_max`, metaVal.max);
        }
      }
    });

    const buildPrettySegments = () => {
      const segments = [];
      Object.entries(context.state.filters.taxonomy || {}).forEach(([taxonomy, terms]) => {
        if (terms && terms.length) {
          segments.push(`tax-${taxonomy}`);
          segments.push(terms.map(slugify).join('+'));
        }
      });

      if (context.state.filters.search) {
        segments.push('search');
        segments.push(slugify(context.state.filters.search));
      }

      if (context.state.filters.price) {
        const { min, max, bucket } = context.state.filters.price;
        if (bucket !== undefined && bucket !== '') {
          segments.push('price-bucket');
          segments.push(String(bucket));
        } else if (min || max) {
          segments.push('price');
          segments.push(`${min || ''}-${max || ''}`);
        }
      }

      Object.entries(context.state.filters.meta || {}).forEach(([metaKey, metaVal]) => {
        if (Array.isArray(metaVal) && metaVal.length) {
          segments.push(`meta-${metaKey}`);
          segments.push(metaVal.map(slugify).join('+'));
        } else if (metaVal && (metaVal.min || metaVal.max)) {
          segments.push(`meta-${metaKey}`);
          segments.push(`${metaVal.min || ''}-${metaVal.max || ''}`);
        }
      });

      if (context.state.filters.orderby) {
        segments.push('orderby');
        segments.push(slugify(context.state.filters.orderby));
      }
      if (context.state.filters.order) {
        segments.push('order');
        segments.push(slugify(context.state.filters.order));
      }
      if (context.state.page && context.state.page !== 1) {
        segments.push('page');
        segments.push(String(context.state.page));
      }
      return segments;
    };

    // SEO-friendly-ish path + query
    const encoded = window.btoa(
      unescape(encodeURIComponent(JSON.stringify(context.state.filters || {})))
    );
    if (encoded) {
      params.set('ka_filters', encoded);
    }

    const prettySegments = buildPrettySegments();
    const basePath = window.location.pathname.replace(/\/filters\/[^/]+(\/.*)?$/, '');
    const prettyPath =
      prettySegments.length > 0
        ? `${basePath.replace(/\/$/, '')}/filters/${context.state.queryId}/${prettySegments.join(
            '/'
          )}`
        : basePath;

    const newUrl =
      prettyPath +
      (params.toString() ? `?${params.toString()}` : '') +
      window.location.hash;
    window.history.replaceState({}, '', newUrl);
  };

  const hydrateStateFromUrl = (context) => {
    const params = new URLSearchParams(window.location.search);
    const prefix = `ka_${context.state.queryId}_`;

    // Pretty path segments
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    const filtersIdx = pathParts.indexOf('filters');
    if (filtersIdx !== -1 && pathParts[filtersIdx + 1]) {
      const pathQueryId = pathParts[filtersIdx + 1];
      if (!context.state.queryId) {
        context.state.queryId = pathQueryId;
      }
      let i = filtersIdx + 2;
      while (i < pathParts.length) {
        const key = pathParts[i];
        const val = pathParts[i + 1] || '';
        if (!key) {
          i += 2;
          continue;
        }
        if (key.startsWith('tax-')) {
          const taxonomy = key.replace('tax-', '');
          const terms = val.split('+').map(decodeSlug).filter(Boolean);
          if (!context.state.filters.taxonomy[taxonomy]) {
            context.state.filters.taxonomy[taxonomy] = [];
          }
          context.state.filters.taxonomy[taxonomy] = terms;
        } else if (key === 'price') {
          const [min, max] = val.split('-');
          context.state.filters.price.min = min || '';
          context.state.filters.price.max = max || '';
        } else if (key === 'price-bucket') {
          context.state.filters.price.bucket = val;
        } else if (key.startsWith('meta-')) {
          const metaKey = key.replace('meta-', '');
          if (val.includes('-')) {
            const [min, max] = val.split('-');
            context.state.filters.meta[metaKey] = { min: min || '', max: max || '' };
          } else {
            context.state.filters.meta[metaKey] = val
              .split('+')
              .map(decodeSlug)
              .filter(Boolean);
          }
        } else if (key === 'search') {
          context.state.filters.search = decodeSlug(val);
        } else if (key === 'orderby') {
          context.state.filters.orderby = decodeSlug(val);
        } else if (key === 'order') {
          context.state.filters.order = decodeSlug(val);
        } else if (key === 'page') {
          context.state.page = parseInt(val, 10) || 1;
        }
        i += 2;
      }
    }

    // Encoded filters fallback
    const encodedFilters = params.get('ka_filters');
    if (encodedFilters) {
      try {
        const decoded = JSON.parse(decodeURIComponent(escape(window.atob(encodedFilters))));
        if (decoded && typeof decoded === 'object') {
          context.state.filters = Object.assign(context.state.filters, decoded);
        }
      } catch (e) {
        // ignore
      }
    }

    params.forEach((value, key) => {
      if (!key.startsWith(prefix)) {
        return;
      }

      const trimmedKey = key.replace(prefix, '');

      if (trimmedKey.startsWith('tax_')) {
        const taxonomy = trimmedKey.replace('tax_', '');
        const terms = value.split(',').filter(Boolean);
        if (!context.state.filters.taxonomy[taxonomy]) {
          context.state.filters.taxonomy[taxonomy] = [];
        }
        context.state.filters.taxonomy[taxonomy] = terms;
      } else if (trimmedKey === 'search') {
        context.state.filters.search = value;
      } else if (trimmedKey === 'price_min') {
        context.state.filters.price.min = value;
      } else if (trimmedKey === 'price_max') {
        context.state.filters.price.max = value;
      } else if (trimmedKey === 'price_bucket') {
        context.state.filters.price.bucket = value;
      } else if (trimmedKey === 'page') {
        context.state.page = parseInt(value, 10) || 1;
      } else if (trimmedKey === 'orderby') {
        context.state.filters.orderby = value;
      } else if (trimmedKey === 'order') {
        context.state.filters.order = value;
      } else if (trimmedKey.startsWith('meta_')) {
        const rest = trimmedKey.replace('meta_', '');
        if (rest.endsWith('_min')) {
          const metaKey = rest.replace('_min', '');
          if (!context.state.filters.meta[metaKey]) {
            context.state.filters.meta[metaKey] = {};
          }
          context.state.filters.meta[metaKey].min = value;
        } else if (rest.endsWith('_max')) {
          const metaKey = rest.replace('_max', '');
          if (!context.state.filters.meta[metaKey]) {
            context.state.filters.meta[metaKey] = {};
          }
          context.state.filters.meta[metaKey].max = value;
        } else {
          const metaKey = rest;
          const vals = value.split(',').filter(Boolean);
          context.state.filters.meta[metaKey] = vals;
        }
      }
    });
  };

  const syncFiltersUI = (context) => {
    const queryId = context.state.queryId;
    const filterElements = findFilters().filter(
      (el) => el.dataset.kaFiltersQueryId === queryId
    );

    filterElements.forEach((element) => {
      const type = element.dataset.kaFilterType;
      if (type === 'taxonomy') {
        const taxonomy = element.dataset.kaTaxonomy;
        const term = element.dataset.kaTerm;
        const selected =
          (context.state.filters.taxonomy[taxonomy] || []).indexOf(term) !== -1;
        if (element.checked !== undefined) {
          element.checked = selected;
        }
      }

      if (type === 'price') {
        const role = element.dataset.kaPriceRole;
        if (role === 'min' && context.state.filters.price.min !== undefined) {
          element.value = context.state.filters.price.min;
        }
        if (role === 'max' && context.state.filters.price.max !== undefined) {
          element.value = context.state.filters.price.max;
        }
      }

      if (type === 'price-bucket') {
        const isActive =
          context.state.filters.price.bucket !== undefined &&
          context.state.filters.price.bucket !== '' &&
          context.state.filters.price.bucket === element.dataset.kaPriceBucket;
        element.classList.toggle('is-active', isActive);
        const li = element.closest('li');
        if (li) {
          li.classList.toggle('is-active', isActive);
        }
      }

      if (type === 'meta' && element.classList.contains('ka-facet-meta__select')) {
        element.classList.toggle('is-active', !!element.value);
      }

      if (type === 'search' && context.state.filters.search) {
        element.value = context.state.filters.search;
      }
    });

    renderActiveFilters(context);
  };

  const applyTaxonomy = (context, taxonomy, term, isChecked) => {
    if (!context.state.filters.taxonomy[taxonomy]) {
      context.state.filters.taxonomy[taxonomy] = [];
    }

    const list = context.state.filters.taxonomy[taxonomy];
    if (isChecked) {
      if (!list.includes(term)) {
        list.push(term);
      }
    } else {
      context.state.filters.taxonomy[taxonomy] = list.filter(
        (item) => item !== term
      );
    }
  };

  const applyPrice = (context, role, value) => {
    if (!context.state.filters.price) {
      context.state.filters.price = {};
    }

    context.state.filters.price[role] = value === '' ? undefined : value;
  };

  const applySearch = (context, value) => {
    context.state.filters.search = value;
  };

  const applySort = (context, orderby, order) => {
    context.state.filters.orderby = orderby;
    context.state.filters.order = order;
  };

  const clearFilters = (context) => {
    context.state.filters = {
      taxonomy: {},
      meta: {},
      price: {},
      search: '',
      orderby: '',
      order: '',
    };
    context.state.page = 1;
    syncFiltersUI(context);
  };

  const renderActiveFilters = (context) => {
    const containers = document.querySelectorAll(
      `[data-ka-filter-type="active-filters"][data-ka-filters-query-id="${context.state.queryId}"]`
    );

    containers.forEach((container) => {
      const list = document.createElement('ul');
      list.className = 'king-addons-active-filters__list';

      Object.entries(context.state.filters.taxonomy || {}).forEach(
        ([taxonomy, terms]) => {
          terms.forEach((term) => {
            const item = document.createElement('li');
            item.className = 'king-addons-active-filters__item';
            item.textContent = `${taxonomy}: ${term}`;
            item.dataset.kaFilterType = 'active-filters';
            item.dataset.kaFiltersQueryId = context.state.queryId;
            item.dataset.kaFilterRemoveType = 'taxonomy';
            item.dataset.kaTaxonomy = taxonomy;
            item.dataset.kaTerm = term;
            list.appendChild(item);
          });
        }
      );

      Object.entries(context.state.filters.meta || {}).forEach(([metaKey, metaVal]) => {
        if (Array.isArray(metaVal)) {
          metaVal.forEach((val) => {
            const item = document.createElement('li');
            item.className = 'king-addons-active-filters__item';
            item.textContent = `${metaKey}: ${val}`;
            item.dataset.kaFilterType = 'active-filters';
            item.dataset.kaFiltersQueryId = context.state.queryId;
            item.dataset.kaFilterRemoveType = 'meta';
            item.dataset.kaMetaKey = metaKey;
            item.dataset.kaMetaValue = val;
            list.appendChild(item);
          });
        } else if (metaVal && (metaVal.min || metaVal.max)) {
          const item = document.createElement('li');
          item.className = 'king-addons-active-filters__item';
          item.textContent = `${metaKey}: ${metaVal.min || ''}-${metaVal.max || ''}`;
          item.dataset.kaFilterType = 'active-filters';
          item.dataset.kaFiltersQueryId = context.state.queryId;
          item.dataset.kaFilterRemoveType = 'meta-range';
          item.dataset.kaMetaKey = metaKey;
          list.appendChild(item);
        }
      });

      if (
        context.state.filters.price &&
        (context.state.filters.price.min || context.state.filters.price.max)
      ) {
        const item = document.createElement('li');
        item.className = 'king-addons-active-filters__item';
        item.textContent = `price: ${context.state.filters.price.min || ''} - ${
          context.state.filters.price.max || ''
        }`;
        item.dataset.kaFilterType = 'active-filters';
        item.dataset.kaFiltersQueryId = context.state.queryId;
        item.dataset.kaFilterRemoveType = 'price';
        list.appendChild(item);
      }

      if (context.state.filters.price && context.state.filters.price.bucket !== undefined) {
        const item = document.createElement('li');
        item.className = 'king-addons-active-filters__item';
        const label = context.state.filters.price.label || context.state.filters.price.bucket;
        item.textContent = `price: ${label}`;
        item.dataset.kaFilterType = 'active-filters';
        item.dataset.kaFiltersQueryId = context.state.queryId;
        item.dataset.kaFilterRemoveType = 'price-bucket';
        list.appendChild(item);
      }

      if (context.state.filters.search) {
        const item = document.createElement('li');
        item.className = 'king-addons-active-filters__item';
        item.textContent = `search: ${context.state.filters.search}`;
        item.dataset.kaFilterType = 'active-filters';
        item.dataset.kaFiltersQueryId = context.state.queryId;
        item.dataset.kaFilterRemoveType = 'search';
        list.appendChild(item);
      }

      if (context.state.filters.orderby) {
        const item = document.createElement('li');
        item.className = 'king-addons-active-filters__item';
        item.textContent = `sort: ${context.state.filters.orderby} ${context.state.filters.order || ''}`.trim();
        item.dataset.kaFilterType = 'active-filters';
        item.dataset.kaFiltersQueryId = context.state.queryId;
        item.dataset.kaFilterRemoveType = 'sort';
        list.appendChild(item);
      }

      if (context.state.page && context.state.page > 1) {
        const item = document.createElement('li');
        item.className = 'king-addons-active-filters__item';
        item.textContent = `page: ${context.state.page}`;
        item.dataset.kaFilterType = 'active-filters';
        item.dataset.kaFiltersQueryId = context.state.queryId;
        item.dataset.kaFilterRemoveType = 'page';
        list.appendChild(item);
      }

      if (container.firstChild) {
        container.innerHTML = '';
      }
      container.appendChild(list);
    });
  };

  const renderCounts = (context) => {
    const counts = context.state.counts || {};
    const queryId = context.state.queryId;
    document
      .querySelectorAll(`[data-ka-filters-query-id="${queryId}"] [data-ka-count]`)
      .forEach((node) => {
        const key = node.dataset.kaCount || '';
        const hideZero = node.dataset.kaHideZero === '1';
        const [taxonomy, term] = key.split(':');
        const value =
          (counts.taxonomy && counts.taxonomy[taxonomy] && counts.taxonomy[taxonomy][term]) || 0;
        node.textContent = `(${value})`;
        if (hideZero) {
          const li = node.closest('li');
          if (li) {
            li.style.display = value === 0 ? 'none' : '';
            return;
          }
        }
        const li = node.closest('li');
        if (li) {
          li.style.display = '';
          if (value === 0) {
            li.classList.add('is-disabled');
            const input = li.querySelector('input');
            if (input) {
              input.disabled = true;
            }
          } else {
            li.classList.remove('is-disabled');
            const input = li.querySelector('input');
            if (input) {
              input.disabled = false;
            }
          }
        }
      });

    // Price buckets
    const bucketCounts = document.querySelectorAll(
      `[data-ka-filters-query-id="${queryId}"] .king-addons-facet__bucket-count[data-ka-price-bucket]`
    );
    bucketCounts.forEach((node) => {
      const bucketKey = node.dataset.kaPriceBucket || '';
      const hideZero = node.dataset.kaHideZero === '1';
      const value = (counts.price && counts.price[bucketKey]) || 0;
      node.textContent = `(${value})`;
      const li = node.closest('li');
      if (li) {
        if (hideZero) {
          li.style.display = value === 0 ? 'none' : '';
        }
        li.classList.toggle('is-disabled', value === 0);
      }
      const button = node.closest('[data-ka-filter-type="price-bucket"]');
      if (button) {
        button.classList.toggle('is-disabled', value === 0);
      }
    });

    document
      .querySelectorAll(`[data-ka-filters-query-id="${queryId}"] [data-ka-meta-count]`)
      .forEach((node) => {
        const key = node.dataset.kaMetaCount || '';
        const hideZero = node.dataset.kaHideZero === '1';
        const [metaKey, metaVal] = key.split(':');
        const value = (counts.meta && counts.meta[metaKey] && counts.meta[metaKey][metaVal]) || 0;
        node.textContent = `(${value})`;
        if (hideZero) {
          const li = node.closest('li');
          if (li) {
            li.style.display = value === 0 ? 'none' : '';
            return;
          }
        }
        const li = node.closest('li');
        if (li) {
          li.style.display = '';
          if (value === 0) {
            li.classList.add('is-disabled');
            const input = li.querySelector('input');
            if (input) {
              input.disabled = true;
            }
          } else {
            li.classList.remove('is-disabled');
            const input = li.querySelector('input');
            if (input) {
              input.disabled = false;
            }
          }
        }
      });
  };

  const applyMetaSelectOptions = (context, counts) => {
    if (!counts || !counts.meta) return;
    const queryId = context.state.queryId;
    Object.entries(counts.meta).forEach(([metaKey, metaCounts]) => {
      const selects = document.querySelectorAll(
        `.ka-facet-meta__select[data-ka-filters-query-id="${queryId}"][data-ka-meta-key="${metaKey}"]`
      );
      selects.forEach((select) => {
        const current = select.value;
        const placeholder = select.dataset.placeholder || 'Any';
        select.innerHTML = `<option value="">${placeholder}</option>`;
        Object.entries(metaCounts).forEach(([val, num]) => {
          const opt = document.createElement('option');
          opt.value = val;
          opt.textContent = `${val} (${num})`;
          select.appendChild(opt);
        });
        if (current) {
          select.value = current;
        }
        select.classList.toggle('is-active', !!select.value);
      });
    });
  };

  const replaceGridHtml = (context, html) => {
    if (!context.element || !html) {
      return;
    }

    const wrap = document.createElement('div');
    wrap.innerHTML = html;
    const newElement = wrap.firstElementChild;
    if (!newElement) {
      return;
    }

    context.element.replaceWith(newElement);
    context.element = newElement;
    ensureContext(newElement);
  };

  const renderPagination = (context, meta) => {
    if (!context) {
      return;
    }

    if (!context.paginationEl) {
      context.paginationEl = document.createElement('div');
      context.paginationEl.className = 'ka-filters-pagination';
      context.element?.after(context.paginationEl);
    }

    if (meta && meta.pagination) {
      context.paginationEl.innerHTML = meta.pagination;
    } else {
      context.paginationEl.innerHTML = '';
    }
  };

  const requestUpdate = (context) =>
    new Promise((resolve) => {
      if (!context || context.loading) {
        resolve(null);
        return;
      }

      context.loading = true;
      const payload = buildPayload(context);
      const body = new URLSearchParams();
      body.append('action', KingAddonsFacetedFilters.action);
      body.append('nonce', KingAddonsFacetedFilters.nonce);
      body.append('payload', JSON.stringify(payload));

      fetch(KingAddonsFacetedFilters.ajaxUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: body.toString(),
        credentials: 'same-origin',
      })
        .then((response) => response.json())
        .then((response) => {
          if (response && response.success && response.data) {
            if (response.data.html) {
              replaceGridHtml(context, response.data.html);
            }
            renderPagination(context, {
              pagination: response.data.pagination,
              total: response.data.total,
              totalPages: response.data.total_pages,
              currentPage: response.data.current_page,
            });
            if (response.data.counts) {
              context.state.counts = response.data.counts;
              renderCounts(context);
            }
          }
          resolve(response);
        })
        .catch(() => resolve(null))
        .finally(() => {
          context.loading = false;
          renderActiveFilters(context);
        });
    });

  const handleFilterEvent = (element) => {
    const queryId = element.dataset.kaFiltersQueryId || '';
    const context = contexts[queryId];
    if (!context) {
      return;
    }

    const type = element.dataset.kaFilterType;

    if (type === 'taxonomy') {
      const taxonomy = element.dataset.kaTaxonomy;
      const term = element.dataset.kaTerm;
      applyTaxonomy(context, taxonomy, term, element.checked);
      context.state.page = 1;
      updateUrl(context);
      renderActiveFilters(context);
      requestUpdate(context);
    }

    if (type === 'price') {
      const role = element.dataset.kaPriceRole;
      applyPrice(context, role, element.value);
      context.state.page = 1;
      updateUrl(context);
      renderActiveFilters(context);
      requestUpdate(context);
    }

    if (type === 'meta') {
      const metaKey = element.dataset.kaMetaKey;
      const role = element.dataset.kaMetaRole || '';
      if (role === 'min' || role === 'max') {
        if (!context.state.filters.meta[metaKey]) {
          context.state.filters.meta[metaKey] = {};
        }
        context.state.filters.meta[metaKey][role] = element.value;
      } else if (role === 'equals') {
        context.state.filters.meta[metaKey] = element.value ? [element.value] : [];
      } else {
        const val = element.dataset.kaMetaValue || '';
        if (!context.state.filters.meta[metaKey]) {
          context.state.filters.meta[metaKey] = [];
        }
        const list = context.state.filters.meta[metaKey];
        if (element.checked) {
          if (!list.includes(val)) {
            list.push(val);
          }
        } else {
          context.state.filters.meta[metaKey] = list.filter((item) => item !== val);
        }
      }
      context.state.page = 1;
      updateUrl(context);
      renderActiveFilters(context);
      requestUpdate(context);
    }

    if (type === 'price-bucket') {
      if (!context.state.filters.price) {
        context.state.filters.price = {};
      }
      const bucket = element.dataset.kaPriceBucket || '';
      const min = element.dataset.kaPriceMin || '';
      const max = element.dataset.kaPriceMax || '';
      const label =
        element.dataset.kaPriceLabel ||
        (element.querySelector('.king-addons-facet__bucket-label')?.textContent || '').trim() ||
        bucket;
      const isSame = context.state.filters.price.bucket === bucket;
      if (isSame) {
        context.state.filters.price = {};
      } else {
        context.state.filters.price.bucket = bucket;
        context.state.filters.price.min = min;
        context.state.filters.price.max = max;
        context.state.filters.price.label = label;
      }
      context.state.page = 1;
      syncFiltersUI(context);
      updateUrl(context);
      renderActiveFilters(context);
      requestUpdate(context);
    }

    if (type === 'search') {
      debounce(`search-${queryId}`, () => {
        applySearch(context, element.value);
        context.state.page = 1;
        updateUrl(context);
        renderActiveFilters(context);
        requestUpdate(context);
      }, 400);
    }

    if (type === 'reset') {
      clearFilters(context);
      updateUrl(context);
      renderActiveFilters(context);
      requestUpdate(context);
    }

    if (type === 'active-filters' && element.dataset.kaFilterRemoveType) {
      const removeType = element.dataset.kaFilterRemoveType;
      if (removeType === 'taxonomy') {
        applyTaxonomy(
          context,
          element.dataset.kaTaxonomy,
          element.dataset.kaTerm,
          false
        );
      }
      if (removeType === 'price') {
        context.state.filters.price = {};
      }
      if (removeType === 'price-bucket') {
        context.state.filters.price = {};
      }
      if (removeType === 'meta') {
        const metaKey = element.dataset.kaMetaKey;
        const metaVal = element.dataset.kaMetaValue;
        if (context.state.filters.meta[metaKey]) {
          context.state.filters.meta[metaKey] = (context.state.filters.meta[metaKey] || []).filter(
            (val) => val !== metaVal
          );
        }
      }
      if (removeType === 'meta-range') {
        const metaKey = element.dataset.kaMetaKey;
        if (context.state.filters.meta[metaKey]) {
          context.state.filters.meta[metaKey] = {};
        }
      }
      if (removeType === 'sort') {
        context.state.filters.orderby = '';
        context.state.filters.order = '';
      }
      if (removeType === 'page') {
        context.state.page = 1;
      }
      if (removeType === 'search') {
        context.state.filters.search = '';
      }
      context.state.page = 1;
      syncFiltersUI(context);
      updateUrl(context);
      renderActiveFilters(context);
      requestUpdate(context);
    }

    if (type === 'sort') {
      const orderby = element.dataset.kaOrderby || '';
      const order = element.dataset.kaOrder || '';
      applySort(context, orderby, order);
      context.state.page = 1;
      updateUrl(context);
      renderActiveFilters(context);
      requestUpdate(context);
    }

    if (type === 'pagination') {
      const page = parseInt(element.dataset.kaPage || '1', 10) || 1;
      context.state.page = page;
      updateUrl(context);
      requestUpdate(context);
    }
  };

  const bindFilterEvents = () => {
    document.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      const filterType = target.dataset.kaFilterType;
      if (!filterType) {
        return;
      }

      if (
        ['taxonomy', 'reset', 'active-filters', 'sort', 'pagination', 'meta', 'price-bucket'].includes(
          filterType
        )
      ) {
        handleFilterEvent(target);
      }
    });

    document.addEventListener('change', (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      const filterType = target.dataset.kaFilterType;
      if (!filterType) {
        return;
      }

      if (['taxonomy', 'price', 'meta'].includes(filterType)) {
        handleFilterEvent(target);
      }
    });

    document.addEventListener('input', (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      const filterType = target.dataset.kaFilterType;
      if (filterType === 'search' || (filterType === 'meta' && (target.dataset.kaMetaRole === 'equals' || target.dataset.kaMetaRole === 'min' || target.dataset.kaMetaRole === 'max'))) {
        handleFilterEvent(target);
      }
    });
  };

  const init = () => {
    const grids = findGrids();
    grids.forEach((grid) => ensureContext(grid));
    bindFilterEvents();
    Object.values(contexts).forEach((context) => syncFiltersUI(context));
  };

  return {
    init,
    ensureContext,
    requestUpdate,
  };
})();

document.addEventListener('DOMContentLoaded', () => {
  if (typeof KAFacetedFilters !== 'undefined') {
    KAFacetedFilters.init();
  }
});






