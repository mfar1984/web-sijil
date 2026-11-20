(() => {
  const pricing = document.querySelector('[data-pricing-tabs]');
  if (!pricing) return;

  const tablist = pricing.querySelector('[data-pricing-tablist]');
  const tabs = [...pricing.querySelectorAll('[data-pricing-tab]')];
  const panels = [...pricing.querySelectorAll('[data-pricing-panel]')];
  const allowedTypes = ['normal', 'government'];
  const query = new URLSearchParams(window.location.search);
  const queryKeys = [...query.keys()];
  const allowedDeepLink = queryKeys.length === 1
    && queryKeys[0] === 'type'
    && query.getAll('type').length === 1
    && allowedTypes.includes(query.get('type'));
  const requestedType = allowedDeepLink ? query.get('type') : null;
  let selectedType = requestedType ?? 'normal';

  document.documentElement.classList.add('pricing-enhanced');
  tablist?.setAttribute('role', 'tablist');
  tablist?.setAttribute('aria-label', 'Plan category');

  tabs.forEach((tab) => {
    const panel = panels.find((item) => item.dataset.pricingPanel === tab.dataset.pricingTab);
    tab.setAttribute('role', 'tab');
    if (panel) tab.setAttribute('aria-controls', panel.id);
  });

  panels.forEach((panel) => {
    const tab = tabs.find((item) => item.dataset.pricingTab === panel.dataset.pricingPanel);
    panel.setAttribute('role', 'tabpanel');
    if (tab) panel.setAttribute('aria-labelledby', tab.id);
  });

  const selectTab = (type, updateUrl = true) => {
    selectedType = allowedTypes.includes(type) ? type : 'normal';

    tabs.forEach((tab) => {
      const selected = tab.dataset.pricingTab === selectedType;
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
      tab.classList.toggle('is-selected', selected);
    });

    panels.forEach((panel) => {
      const selected = panel.dataset.pricingPanel === selectedType;
      panel.hidden = !selected;
    });

    if (updateUrl) {
      const url = new URL(window.location.href);
      url.search = `?type=${selectedType}`;
      window.history.replaceState({}, '', url);
    }
  };

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => selectTab(tab.dataset.pricingTab));
    tab.addEventListener('keydown', (event) => {
      let nextIndex = null;

      if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
      if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs.length;
      if (event.key === 'Home') nextIndex = 0;
      if (event.key === 'End') nextIndex = tabs.length - 1;
      if (nextIndex === null) return;

      event.preventDefault();
      tabs[nextIndex].focus();
      selectTab(tabs[nextIndex].dataset.pricingTab);
    });
  });

  if (window.location.search && !allowedDeepLink) {
    window.history.replaceState({}, '', window.location.pathname + window.location.hash);
  }

  selectTab(selectedType, false);
})();
