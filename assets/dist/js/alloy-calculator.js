(function () {
	function toCurrency(value) {
		const amount = Number.isFinite(value) ? value : 0;

		return new Intl.NumberFormat("en-US", {
			style: "currency",
			currency: "USD",
			minimumFractionDigits: 2,
			maximumFractionDigits: 2
		}).format(amount);
	}

	function convertWeightToGrams(weight, unit) {
		switch (unit) {
			case "ounces":
				return weight * 31.1035;
			case "pennyweight":
				return weight * 1.55517384;
			case "grams":
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

	function calculate(container) {
		const pricePerGram = parseFloat(container.dataset.basePrice || "0");
		const karatSelect = container.querySelector(".js-alloy-calculator-karat");
		const weightUnitSelect = container.querySelector(".js-alloy-calculator-weight-unit");
		const weightInput = container.querySelector(".js-alloy-calculator-weight");
		const marketValue = container.querySelector(".js-alloy-calculator-market-value");
		const pawnValue = container.querySelector(".js-alloy-calculator-pawn-value");
		const alloyValue = container.querySelector(".js-alloy-calculator-alloy-value");
		const results = container.querySelector(".js-alloy-calculator-results");
		const cta = container.querySelector(".js-alloy-calculator-cta");

		const karat = parseFloat(karatSelect ? karatSelect.value : "24");
		const weight = parseFloat(weightInput ? weightInput.value : "0");
		const weightUnit = weightUnitSelect ? weightUnitSelect.value : "grams";
		const weightInGrams = convertWeightToGrams(Number.isFinite(weight) ? weight : 0, weightUnit);
		const purity = karat / 24;
		const totalValue = pricePerGram * purity * weightInGrams;
		const currentMarketValue = totalValue;
		const averagePawnShopOffer = totalValue * 0.4;
		const alloyEstimatedOffer = totalValue * getAlloyOfferRate(karat);

		if (marketValue) {
			marketValue.textContent = toCurrency(currentMarketValue);
		}

		if (pawnValue) {
			pawnValue.textContent = toCurrency(averagePawnShopOffer);
		}

		if (alloyValue) {
			alloyValue.textContent = toCurrency(alloyEstimatedOffer);
		}

		if (results) {
			results.classList.remove("aur:hidden");
			results.classList.add("aur:grid");
		}

		if (cta) {
			cta.classList.remove("aur:hidden");
		}
	}

	function refreshOfferCard(card) {
		const purity = card.dataset.purity || "24";
		const button = card.querySelector(".js-metal-offer-card-refresh");
		const spot = card.querySelector(".js-metal-offer-card-spot");
		const pawn = card.querySelector(".js-metal-offer-card-pawn");
		const alloy = card.querySelector(".js-metal-offer-card-alloy");

		if (!window.alloyMetalPriceApi || !window.alloyMetalPriceApi.ajaxUrl) {
			return;
		}

		if (button) {
			button.disabled = true;
		}

		const body = new URLSearchParams({
			action: "alloy_metal_price_api_refresh_offer_card",
			nonce: window.alloyMetalPriceApi.refreshNonce || "",
			purity
		});

		fetch(window.alloyMetalPriceApi.ajaxUrl, {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
			},
			body: body.toString()
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (!payload || !payload.success || !payload.data) {
					return;
				}

				if (spot) {
					spot.textContent = payload.data.spot || "Unavailable";
				}

				if (pawn) {
					pawn.textContent = payload.data.pawn || "Unavailable";
				}

				if (alloy) {
					alloy.textContent = payload.data.alloy || "Unavailable";
				}
			})
			.finally(function () {
				if (button) {
					button.disabled = false;
				}
			});
	}

	function initialize(container) {
		const form = container.querySelector(".js-alloy-calculator-form");
		const karatSelect = container.querySelector(".js-alloy-calculator-karat");
		const weightUnitSelect = container.querySelector(".js-alloy-calculator-weight-unit");
		const weightInput = container.querySelector(".js-alloy-calculator-weight");
		const calculateButton = container.querySelector(".js-alloy-calculator-calculate");
		const defaultKarat = container.dataset.defaultKarat;

		if (karatSelect && defaultKarat) {
			karatSelect.value = defaultKarat;
		}

		if (form) {
			form.addEventListener("submit", function (event) {
				event.preventDefault();
				calculate(container);
			});
		}

		if (calculateButton) {
			calculateButton.addEventListener("click", function (event) {
				event.preventDefault();
				calculate(container);
			});
		}
	}

	function initializeOfferCard(card) {
		const button = card.querySelector(".js-metal-offer-card-refresh");

		if (button) {
			button.addEventListener("click", function () {
				refreshOfferCard(card);
			});
		}
	}

	document.addEventListener("DOMContentLoaded", function () {
		document.querySelectorAll(".js-alloy-calculator").forEach(function (container) {
			initialize(container);
		});

		document.querySelectorAll(".js-metal-offer-card").forEach(function (card) {
			initializeOfferCard(card);
		});
	});
})();
