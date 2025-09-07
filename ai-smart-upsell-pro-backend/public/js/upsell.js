(function () {
    const shop = Shopify?.shop || '';
    const productId = window.meta?.product?.id || window.__st?.rid || '';
    const visitorId = window.Shopify?.Analytics?.meta?.page?.view?.id || '';

    if (!shop || !productId) return;
    if (document.getElementById('pro-upsell-container')) return; // Prevent duplicate inserts

    const abTest = 1; // Replace or toggle for A/B testing as needed
    const endpoint = `https://shopifybackend.bcstdr.site/api/upsells?shop=${encodeURIComponent(shop)}&product_id=${encodeURIComponent(productId)}&visitor_id=${encodeURIComponent(visitorId)}`;

    fetch(endpoint, {
        headers: {
            'Accept': 'application/json',
            'ngrok-skip-browser-warning': 'true',
        }
    })
        .then(res => res.json())
        .then(data => {
            const upsells = data.upsells || [];
            if (!upsells.length) return; // No upsells, exit early

            // Create container div
            const container = document.createElement('div');
            container.id = 'pro-upsell-container';
            container.style.margin = '48px 0';
            container.style.border = '1px solid #ddd';
            container.style.padding = '20px';
            container.style.background = '#f8f8f8';
            container.style.borderRadius = '8px';
            container.style.fontFamily = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen";

            // Add widget heading
            const heading = document.createElement('h3');
            heading.textContent = 'You might also like';
            heading.style.fontSize = '1.5rem';
            heading.style.marginBottom = '16px';
            heading.style.color = '#1a1a1a';
            container.appendChild(heading);

            // Create slider div
            const slider = document.createElement('div');
            slider.id = 'pro-upsell-slider';
            slider.className = 'pro-upsell-slider';
            slider.style.position = 'relative';
            slider.style.display = 'flex';
            slider.style.overflowX = 'auto';
            slider.style.gap = '12px';
            slider.style.scrollSnapType = 'x mandatory';
            slider.style.scrollBehavior = 'smooth';
            slider.style.paddingBottom = '12px';

            // Append upsell items
            upsells.forEach(({title, image, price, handle, variant_id}) => {
                const item = document.createElement('div');
                item.className = 'pro-upsell-item';
                item.style.scrollSnapAlign = 'start';
                item.style.flex = '0 0 140px';
                item.style.background = '#fff';
                item.style.border = '1px solid #eee';
                item.style.borderRadius = '12px';
                item.style.boxShadow = '0 2px 6px rgba(0,0,0,0.1)';
                item.style.textAlign = 'center';
                item.style.padding = '10px';
                item.style.display = 'flex';
                item.style.flexDirection = 'column';
                item.style.justifyContent = 'space-between';
                item.style.height = '280px';

                item.innerHTML = `
        <img src="${image}" alt="${title}" style="width:100%;height:140px;object-fit:cover;border-radius:8px; margin-bottom:10px;" />
        <div class="pro-upsell-title" style="font-weight:600; font-size:14px; color:#222; margin:6px 0;flex-grow:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
          ${title}
        </div>
        <div class="pro-upsell-price" style="font-size:13px; color:#555; margin-bottom:8px;">
          ${data.money_format.replace('{{amount}}', price)}
        </div>
        <button type="button" class="pro-upsell-add-btn" aria-label="Add ${title} to cart" style="
          background-color: white;
          color: #000;
          border-radius: 3px;
          padding: 8px 6px;
          font-size: 13px;
          font-weight: 500;
          cursor: pointer;
          border: 1px solid #000;
          user-select: none;
          letter-spacing: 0.1rem;
          transition: background 0.3s ease;
        ">Add to cart</button>
      `;

                const addButton = item.querySelector('.pro-upsell-add-btn');

                addButton.addEventListener('click', () => {
                    addButton.textContent = 'Adding to Cart...';

                    // Track click
                    fetch('https://shopifybackend.bcstdr.site/api/upsell-clicks', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'ngrok-skip-browser-warning': 'true'},
                        body: JSON.stringify({shop, product_id: productId, upsell_id: handle, visitor_id: visitorId})
                    }).catch(() => {});

                    // Add product to cart AJAX API
                    fetch('/cart/add.js', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({items: [{id: variant_id || upsells[0].variant_id, quantity: 1}]})
                    })
                        .then(resp => resp.json())
                        .then(() => {
                            addButton.textContent = 'Added!';
                            setTimeout(() => {
                                addButton.textContent = 'Add to cart';
                            }, 1500);
                            // Optionally reload or update mini cart here
                            window.location.reload();
                        })
                        .catch(() => {
                            addButton.textContent = 'Add to cart';
                        });
                });

                slider.appendChild(item);

                // Track impression once per upsell
                fetch('https://shopifybackend.bcstdr.site/api/upsell-impressions', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'ngrok-skip-browser-warning': 'true'},
                    body: JSON.stringify({shop, product_id: productId, upsell_id: handle, visitor_id: visitorId})
                }).catch(() => {});
            });

            container.appendChild(slider);

            // Create navigation buttons
            const prevBtn = document.createElement('div');
            prevBtn.className = 'pro-upsell-nav pro-upsell-prev';
            prevBtn.id = 'pro-upsell-prev';
            prevBtn.textContent = '❮';
            prevBtn.style.position = 'absolute';
            prevBtn.style.top = '50%';
            prevBtn.style.left = '0';
            prevBtn.style.transform = 'translateY(-50%)';
            prevBtn.style.width = '30px';
            prevBtn.style.height = '30px';
            prevBtn.style.background = 'rgba(0,0,0,0.2)';
            prevBtn.style.borderRadius = '50%';
            prevBtn.style.color = 'white';
            prevBtn.style.fontSize = '20px';
            prevBtn.style.textAlign = 'center';
            prevBtn.style.lineHeight = '30px';
            prevBtn.style.cursor = 'pointer';
            prevBtn.style.userSelect = 'none';
            prevBtn.style.zIndex = '10';

            const nextBtn = document.createElement('div');
            nextBtn.className = 'pro-upsell-nav pro-upsell-next';
            nextBtn.id = 'pro-upsell-next';
            nextBtn.textContent = '❯';
            nextBtn.style.position = 'absolute';
            nextBtn.style.top = '50%';
            nextBtn.style.right = '0';
            nextBtn.style.transform = 'translateY(-50%)';
            nextBtn.style.width = '30px';
            nextBtn.style.height = '30px';
            nextBtn.style.background = 'rgba(0,0,0,0.2)';
            nextBtn.style.borderRadius = '50%';
            nextBtn.style.color = 'white';
            nextBtn.style.fontSize = '20px';
            nextBtn.style.textAlign = 'center';
            nextBtn.style.lineHeight = '30px';
            nextBtn.style.cursor = 'pointer';
            nextBtn.style.userSelect = 'none';
            nextBtn.style.zIndex = '10';

            container.appendChild(prevBtn);
            container.appendChild(nextBtn);

            // Insert container below product add to cart form or at bottom if none
            const form = document.querySelector('form[action="/cart/add"]');
            if (form) {
                form.insertAdjacentElement('afterend', container);
            } else {
                document.body.appendChild(container);
            }

            // Add navigation functionality
            prevBtn.addEventListener('click', () => slider.scrollBy({left: -150, behavior: 'smooth'}));
            nextBtn.addEventListener('click', () => slider.scrollBy({left: 150, behavior: 'smooth'}));
        })
        .catch(console.error);
})();
