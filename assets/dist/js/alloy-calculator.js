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
		const displayPrice = container.querySelector(".js-alloy-calculator-display-price");
		const marketValue = container.querySelector(".js-alloy-calculator-market-value");
		const pawnValue = container.querySelector(".js-alloy-calculator-pawn-value");
		const alloyValue = container.querySelector(".js-alloy-calculator-alloy-value");

		const karat = parseFloat(karatSelect ? karatSelect.value : "24");
		const weight = parseFloat(weightInput ? weightInput.value : "0");
		const weightUnit = weightUnitSelect ? weightUnitSelect.value : "grams";
		const weightInGrams = convertWeightToGrams(Number.isFinite(weight) ? weight : 0, weightUnit);
		const purity = karat / 24;
		const totalValue = pricePerGram * purity * weightInGrams;
		const currentMarketValue = totalValue;
		const averagePawnShopOffer = totalValue * 0.4;
		const alloyEstimatedOffer = totalValue * getAlloyOfferRate(karat);

		if (displayPrice) {
			displayPrice.textContent = pricePerGram.toFixed(2);
		}

		if (marketValue) {
			marketValue.textContent = toCurrency(currentMarketValue);
		}

		if (pawnValue) {
			pawnValue.textContent = toCurrency(averagePawnShopOffer);
		}

		if (alloyValue) {
			alloyValue.textContent = toCurrency(alloyEstimatedOffer);
		}
	}

	function initialize(container) {
		const karatSelect = container.querySelector(".js-alloy-calculator-karat");
		const weightUnitSelect = container.querySelector(".js-alloy-calculator-weight-unit");
		const weightInput = container.querySelector(".js-alloy-calculator-weight");
		const calculateButton = container.querySelector(".js-alloy-calculator-calculate");
		const defaultKarat = container.dataset.defaultKarat;

		if (karatSelect && defaultKarat) {
			karatSelect.value = defaultKarat;
		}

		if (calculateButton) {
			calculateButton.addEventListener("click", function () {
				calculate(container);
			});
		}

		if (karatSelect) {
			karatSelect.addEventListener("change", function () {
				calculate(container);
			});
		}

		if (weightUnitSelect) {
			weightUnitSelect.addEventListener("change", function () {
				calculate(container);
			});
		}

		if (weightInput) {
			weightInput.addEventListener("input", function () {
				calculate(container);
			});
		}

		calculate(container);
	}

	document.addEventListener("DOMContentLoaded", function () {
		document.querySelectorAll(".js-alloy-calculator").forEach(function (container) {
			initialize(container);
		});
	});
})();
