(function () {
	function toCurrency(value) {
		const amount = Number.isFinite(value) ? value : 0;

		return new Intl.NumberFormat('en-US', {
			style: 'currency',
			currency: 'USD',
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		}).format(amount);
	}

	function convertWeightToGrams(weight, unit) {
		switch (unit) {
			case 'ounces':
				return weight * 31.1035;
			case 'pennyweight':
				return weight * 1.55517384;
			case 'grams':
			default:
				return weight;
		}
	}

	function getAlloyOfferRate(karat) {
		if (karat === 22 || karat === 24) {
			return 0.85;
		}

		return 0.7;
	}

	function formatNumber(value) {
		const amount = Number.isFinite(value) ? value : 0;

		return (Math.round(amount * 100) / 100).toFixed(2);
	}

	function getStoneDeduction(weightInGrams, stoneMaterial) {
		if (stoneMaterial === 'none') {
			return 0;
		}

		const percentage = 0.2;
		const minimumDeduction = 0.5;
		const maximumDeduction = 3;

		return Math.max(
			minimumDeduction,
			Math.min(weightInGrams * percentage, maximumDeduction),
		);
	}

	function closeTooltip(container) {
		const button = container.querySelector('.js-alloy-calculator-tooltip-button');
		const tooltip = container.querySelector('.js-alloy-calculator-tooltip');

		if (!button || !tooltip) {
			return;
		}

		tooltip.classList.add('aur:hidden');
		button.setAttribute('aria-expanded', 'false');
	}

	function openTooltip(container) {
		const button = container.querySelector('.js-alloy-calculator-tooltip-button');
		const tooltip = container.querySelector('.js-alloy-calculator-tooltip');

		if (!button || !tooltip) {
			return;
		}

		tooltip.classList.remove('aur:hidden');
		button.setAttribute('aria-expanded', 'true');
	}

	function toggleTooltip(container) {
		const tooltip = container.querySelector('.js-alloy-calculator-tooltip');

		if (!tooltip) {
			return;
		}

		if (tooltip.classList.contains('aur:hidden')) {
			openTooltip(container);
			return;
		}

		closeTooltip(container);
	}

	function calculate(container) {
		const pricePerGram = parseFloat(container.dataset.basePrice || '0');
		const metal = container.dataset.metal || 'gold';
		const isClassRing = container.dataset.classring === 'true';
		const purityField = container.querySelector('.js-alloy-calculator-purity');
		const stoneField = container.querySelector('.js-alloy-calculator-stone');
		const weightUnitSelect = container.querySelector('.js-alloy-calculator-weight-unit');
		const weightInput = container.querySelector('.js-alloy-calculator-weight');
		const marketValue = container.querySelector('.js-alloy-calculator-market-value');
		const pawnValue = container.querySelector('.js-alloy-calculator-pawn-value');
		const alloyValue = container.querySelector('.js-alloy-calculator-alloy-value');
		const metaValue = container.querySelector('.js-alloy-calculator-meta');
		const results = container.querySelector('.js-alloy-calculator-results');
		const cta = container.querySelector('.js-alloy-calculator-cta');

		const purityRaw = parseFloat(purityField ? purityField.value : '0');
		const weight = parseFloat(weightInput ? weightInput.value : '0');
		const weightUnit = weightUnitSelect ? weightUnitSelect.value : 'grams';
		const totalWeightInGrams = convertWeightToGrams(
			Number.isFinite(weight) ? weight : 0,
			weightUnit,
		);
		const stoneMaterial = stoneField ? stoneField.value : 'none';
		const stoneDeduction = isClassRing
			? getStoneDeduction(totalWeightInGrams, stoneMaterial)
			: 0;
		const weightInGrams = Math.max(totalWeightInGrams - stoneDeduction, 0);
		const purity =
			metal === 'gold'
				? (Number.isFinite(purityRaw) ? purityRaw : 24) / 24
				: Math.min(1, Math.max(0, Number.isFinite(purityRaw) ? purityRaw : 0.9999));
		const totalValue = pricePerGram * purity * weightInGrams;
		const currentMarketValue = totalValue;
		const averagePawnShopOffer = totalValue * 0.4;
		const alloyEstimatedOffer =
			totalValue * (metal === 'gold' ? getAlloyOfferRate(purityRaw) : 0.7);

		if (marketValue) {
			marketValue.textContent = toCurrency(currentMarketValue);
		}

		if (pawnValue) {
			pawnValue.textContent = toCurrency(averagePawnShopOffer);
		}

		if (alloyValue) {
			alloyValue.textContent = toCurrency(alloyEstimatedOffer);
		}

		if (metaValue) {
			metaValue.textContent =
				'Assumed stone deduction: ' +
				formatNumber(stoneDeduction) +
				' g • Metal-only: ' +
				formatNumber(weightInGrams) +
				' g';
		}

		if (results) {
			results.classList.remove('aur:hidden');
			results.classList.add('aur:grid');
		}

		if (cta) {
			cta.classList.remove('aur:hidden');
		}
	}

	function refreshOfferCard(card) {
		const purity = card.dataset.purity || '24';
		const button = card.querySelector('.js-metal-offer-card-refresh');
		const spot = card.querySelector('.js-metal-offer-card-spot');
		const pawn = card.querySelector('.js-metal-offer-card-pawn');
		const alloy = card.querySelector('.js-metal-offer-card-alloy');

		if (!window.alloyMetalPriceApi || !window.alloyMetalPriceApi.ajaxUrl) {
			return;
		}

		if (button) {
			button.disabled = true;
		}

		const body = new URLSearchParams({
			action: 'alloy_metal_price_api_refresh_offer_card',
			nonce: window.alloyMetalPriceApi.refreshNonce || '',
			purity,
		});

		fetch(window.alloyMetalPriceApi.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: body.toString(),
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (!payload || !payload.success || !payload.data) {
					return;
				}

				if (spot) {
					spot.textContent = payload.data.spot || 'Unavailable';
				}

				if (pawn) {
					pawn.textContent = payload.data.pawn || 'Unavailable';
				}

				if (alloy) {
					alloy.textContent = payload.data.alloy || 'Unavailable';
				}
			})
			.finally(function () {
				if (button) {
					button.disabled = false;
				}
			});
	}

	function updateConversionDisplays(container) {
		const hardCost = container.querySelector('.js-conversion-hard-cost');
		const hardCostDisplay = container.querySelector('.js-conversion-hard-cost-display');
		const profitMargin = container.querySelector('.js-conversion-profit-margin');
		const profitMarginDisplay = container.querySelector('.js-conversion-profit-margin-display');

		if (hardCost && hardCostDisplay) {
			hardCostDisplay.textContent = hardCost.value;
		}

		if (profitMargin && profitMarginDisplay) {
			profitMarginDisplay.textContent = profitMargin.value;
		}
	}

	function calculateConversionRate(container) {
		const saleAmountField = container.querySelector('.js-conversion-sale-amount');
		const hardCostField = container.querySelector('.js-conversion-hard-cost');
		const profitMarginField = container.querySelector('.js-conversion-profit-margin');
		const result = container.querySelector('.js-conversion-rate-result');

		const saleAmount = parseFloat(saleAmountField ? saleAmountField.value : '0');
		const hardCost = parseFloat(hardCostField ? hardCostField.value : '0');
		const profitMargin = parseFloat(profitMarginField ? profitMarginField.value : '0') / 100;
		const affiliateCommissionRate = 0.1;
		const safeSaleAmount = Number.isFinite(saleAmount) ? saleAmount : 0;
		const safeHardCost = Number.isFinite(hardCost) ? hardCost : 0;
		const safeProfitMargin = Number.isFinite(profitMargin) ? profitMargin : 0;
		const profitPerSale = safeSaleAmount * safeProfitMargin;
		const affiliateCommission = affiliateCommissionRate * profitPerSale;
		const totalCostPerLead = safeHardCost + affiliateCommission;
		const requiredConversionRate = profitPerSale > 0 ? totalCostPerLead / profitPerSale : 0;
		const totalProfit = profitPerSale * requiredConversionRate - totalCostPerLead;

		if (!result) {
			return;
		}

		result.innerHTML = [
			'<div><strong>Sale Amount:</strong> ' + toCurrency(safeSaleAmount) + '</div>',
			'<div><strong>Required Conversion Rate:</strong> ' +
				(requiredConversionRate * 100).toFixed(2) +
				'%</div>',
			'<div><strong>Profit per Sale:</strong> ' + toCurrency(profitPerSale) + '</div>',
			'<div><strong>Internal Hard Cost:</strong> ' + toCurrency(safeHardCost) + '</div>',
			'<div><strong>Affiliate Commission per Sale:</strong> ' +
				toCurrency(affiliateCommission) +
				'</div>',
			'<div><strong>Total Cost per Lead:</strong> ' + toCurrency(totalCostPerLead) + '</div>',
			'<div><strong>Total Profit:</strong> ' + toCurrency(totalProfit) + '</div>',
		].join('');
	}

	function calculateBudgetBuyWidget(container) {
		const premiumField = container.querySelector('.js-metal-budget-buy-premium');
		const output = container.querySelector('.js-metal-budget-buy-output');
		const budget = parseFloat(container.dataset.budget || '0');
		const spotPerOunce = parseFloat(container.dataset.spotOunce || '0');
		const premium = parseFloat(premiumField ? premiumField.value : '0');
		const safeBudget = Number.isFinite(budget) ? budget : 0;
		const safeSpotPerOunce = Number.isFinite(spotPerOunce) ? spotPerOunce : 0;
		const safePremium = Number.isFinite(premium) ? premium : 0;
		const denominator = safeSpotPerOunce * (1 + safePremium / 100);
		const ounces = denominator > 0 ? safeBudget / denominator : null;

		if (!output) {
			return;
		}

		output.textContent = Number.isFinite(ounces) ? ounces.toFixed(3) : '--';
	}

	function initialize(container) {
		const purityField = container.querySelector('.js-alloy-calculator-purity');
		const defaultPurity = container.dataset.defaultPurity;

		if (purityField && defaultPurity) {
			purityField.value = defaultPurity;
		}

		closeTooltip(container);
	}

	function initializeConversionCalculator(container) {
		updateConversionDisplays(container);
	}

	function initializeBudgetBuyWidget(container) {
		calculateBudgetBuyWidget(container);
	}

	function initializeOfferCard(card) {
		const button = card.querySelector('.js-metal-offer-card-refresh');

		if (button) {
			button.addEventListener('click', function () {
				refreshOfferCard(card);
			});
		}
	}

	function initializeAlloyMetalPriceApi() {
		document.querySelectorAll('.js-alloy-calculator').forEach(function (container) {
			initialize(container);
		});

		document.querySelectorAll('.js-metal-offer-card').forEach(function (card) {
			initializeOfferCard(card);
		});

		document.querySelectorAll('.js-conversion-rate-calculator').forEach(function (container) {
			initializeConversionCalculator(container);
		});

		document.querySelectorAll('.js-metal-budget-buy-widget').forEach(function (container) {
			initializeBudgetBuyWidget(container);
		});
	}

	document.addEventListener('submit', function (event) {
		const form = event.target.closest('.js-alloy-calculator-form');

		if (form) {
			event.preventDefault();

			const container = form.closest('.js-alloy-calculator');

			if (container) {
				calculate(container);
			}

			return;
		}

		const conversionForm = event.target.closest('.js-conversion-rate-calculator-form');

		if (!conversionForm) {
			return;
		}

		event.preventDefault();

		const conversionContainer = conversionForm.closest('.js-conversion-rate-calculator');

		if (conversionContainer) {
			calculateConversionRate(conversionContainer);
		}
	});

	document.addEventListener('click', function (event) {
		const calculateButton = event.target.closest('.js-alloy-calculator-calculate');

		if (calculateButton) {
			event.preventDefault();

			const container = calculateButton.closest('.js-alloy-calculator');

			if (container) {
				calculate(container);
			}

			return;
		}

		const tooltipButton = event.target.closest('.js-alloy-calculator-tooltip-button');

		if (tooltipButton) {
			event.preventDefault();

			const container = tooltipButton.closest('.js-alloy-calculator');

			if (container) {
				toggleTooltip(container);
			}

			return;
		}

		const conversionButton = event.target.closest('.js-conversion-rate-calculate');

		if (conversionButton) {
			event.preventDefault();

			const conversionContainer = conversionButton.closest('.js-conversion-rate-calculator');

			if (conversionContainer) {
				calculateConversionRate(conversionContainer);
			}

			return;
		}

		const refreshButton = event.target.closest('.js-metal-offer-card-refresh');

		if (!refreshButton) {
			document.querySelectorAll('.js-alloy-calculator').forEach(function (container) {
				if (!container.contains(event.target)) {
					closeTooltip(container);
				}
			});

			return;
		}

		const card = refreshButton.closest('.js-metal-offer-card');

		if (card) {
			refreshOfferCard(card);
		}
	});

	document.addEventListener('change', function (event) {
		const field = event.target.closest(
			'.js-alloy-calculator-purity, .js-alloy-calculator-weight-unit, .js-alloy-calculator-stone',
		);

		if (!field) {
			return;
		}

		const container = field.closest('.js-alloy-calculator');

		if (container) {
			calculate(container);
		}
	});

	document.addEventListener('input', function (event) {
		const conversionField = event.target.closest(
			'.js-conversion-hard-cost, .js-conversion-profit-margin',
		);

		if (conversionField) {
			const conversionContainer = conversionField.closest('.js-conversion-rate-calculator');

			if (conversionContainer) {
				updateConversionDisplays(conversionContainer);
			}

			return;
		}

		const budgetBuyField = event.target.closest('.js-metal-budget-buy-premium');

		if (budgetBuyField) {
			const budgetBuyContainer = budgetBuyField.closest('.js-metal-budget-buy-widget');

			if (budgetBuyContainer) {
				calculateBudgetBuyWidget(budgetBuyContainer);
			}

			return;
		}

		const field = event.target.closest('.js-alloy-calculator-weight, .js-alloy-calculator-purity');

		if (!field) {
			return;
		}

		const container = field.closest('.js-alloy-calculator');

		if (container) {
			calculate(container);
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape') {
			return;
		}

		document.querySelectorAll('.js-alloy-calculator').forEach(function (container) {
			closeTooltip(container);
		});
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeAlloyMetalPriceApi);
	} else {
		initializeAlloyMetalPriceApi();
	}
})();
