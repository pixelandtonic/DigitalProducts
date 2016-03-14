(function($){

if (typeof Craft.DigitalProducts === typeof undefined) {
	Craft.DigitalProducts = {};
}

var elementTypeClass = 'DigitalProducts_License';

/**
 * Product index class
 */
Craft.DigitalProducts.LicenseIndex = Craft.BaseElementIndex.extend({

	afterInit: function() {


		this.$btnGroup = $('<div class="btngroup submit"/>');
		var $menuBtn;
		var href = 'href="'+Craft.getUrl('digitalproducts/licenses/new')+'"',
			label = Craft.t('New license');

		this.$newProductBtnGroup = $('<div class="btngroup submit"/>');
		this.$newProductBtn = $('<a class="btn submit add icon" '+href+'>'+label+'</a>').appendTo(this.$newProductBtnGroup);
		
		this.addButton(this.$newProductBtnGroup);
		
		this.base();
	}
});

// Register it!
try {
	Craft.registerElementIndexClass(elementTypeClass, Craft.DigitalProducts.LicenseIndex);
}
catch(e) {
	// Already registered
}

})(jQuery);
