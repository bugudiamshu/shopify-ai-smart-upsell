document.addEventListener('DOMContentLoaded', function () {
    const widgets = document.querySelectorAll('.nitulabs-upsell-widget');

    widgets.forEach(widget => {
        fetch('/cart.js')
            .then(res => res.json())
            .then(cart => {
                let visitorIdBase = widget.dataset.visitorId || 'guest-visitor-no-cart';
                if (cart && cart.token) {
                    visitorIdBase = visitorIdBase.split('visitor-')[0] + 'visitor-' + cart.token;
                }
                widget.dataset.visitorId = visitorIdBase;

                initUpsellWidget(widget);
            })
            .catch(() => initUpsellWidget(widget));
    });
});

function initUpsellWidget(container) {
    const shop          = container.dataset.shop;
    const productId     = container.dataset.productId;
    const visitorId     = container.dataset.visitorId;
    const apiUrl        = container.dataset.apiUrl;
    const clickUrl      = container.dataset.clickUrl;
    const impressionUrl = container.dataset.impressionUrl;

    const listContainer = container.querySelector('.nitulabs-upsell-list');
    const placeholder   = container.querySelector('.nitulabs-upsell-placeholder');

    fetch(`${apiUrl}?shop=${encodeURIComponent(shop)}&product_id=${productId}&visitor_id=${visitorId}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            placeholder && placeholder.remove();

            if (!data.upsells || data.upsells.length === 0) {
                listContainer.innerHTML = '<p class="text-center text-body">' + (window.themeStrings?.noRecommendations || 'No recommendations available.') + '</p>';
                return;
            }

            const moneyFormat = data.money_format;

            data.upsells.forEach(item => {
                const formattedPrice = moneyFormat.replace('{{amount}}', item.price);
                const imgSrc = item.image && item.image.includes('https')
                    ? `${item.image}?width=200&height=200`
                    : 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/default-product.png?v=1';

                const div = document.createElement('div');
                div.className = 'nitulabs-upsell-item';
                div.innerHTML = `
                    <a href="https://${shop}/products/${item.handle}" 
                       class="block group" 
                       data-upsell-id="${item.id}" 
                       style="text-decoration: none">
                        <div class="relative overflow-hidden rounded-md bg-gray-100 aspect-square w-24 h-24 sm:w-28 sm:h-28">
                          <img src="${imgSrc}" alt="${item.title}" 
                               class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" 
                               loading="lazy" />
                        </div>
                        <div class="mt-1">
                          <h3 class="text-base font-semibold line-clamp-2 mb-1">${item.title}</h3>
                          <p class="text-md text-gray-700 no-underline" style="font-size: 1em;">${formattedPrice}</p>
                        </div>
                    </a>
                `;
                listContainer.appendChild(div);
            });

            recordImpression(impressionUrl, {shop, product_id: productId, visitor_id: visitorId});

            // ✅ Fixed click handler
            listContainer.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const href = link.getAttribute('href');
                    const payload = {
                        shop,
                        product_id: productId,
                        recommendation_id: link.dataset.upsellId,
                        visitor_id: visitorId
                    };

                    recordClick(clickUrl, payload, () => {
                        // Redirect after recording
                        window.location.href = href;
                    });
                });
            });

        })
        .catch(err => {
            console.error('Error loading upsell recommendations:', err);
            if (placeholder) placeholder.textContent = 'Failed to load recommendations.';
        });
}

function recordImpression(url, data) {
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).catch(err => console.warn('Failed to record impression:', err));
}

// ✅ recordClick now accepts a callback to redirect
function recordClick(url, data, callback) {
    if (navigator.sendBeacon) {
        const blob = new Blob([JSON.stringify(data)], {type: 'application/json'});
        navigator.sendBeacon(url, blob);
        callback();
    } else {
        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        }).finally(() => callback());
    }
}
