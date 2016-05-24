"use strict";
var fs = require('fs')
  , Docxtemplater = require('docxtemplater');

exports.createWineMenu = (wines, restaurant_name, descriptions) => {
	let data = sortWineByType(wines);
	return fillTemplate(data, restaurant_name, descriptions);
}

var fillTemplate = (wines, restaurant_name, descriptions) => {

  let template = descriptions ? "/wine-menu-template_2.docx" : "/wine-menu-template-no-description.docx";

	var content = fs
    .readFileSync(__dirname + template, "binary");

	var doc = new Docxtemplater(content);

	//set the templateVariables
	doc.setData({
		Title : restaurant_name,
		wines : wines
	});

	//apply them (replace all occurences of {variable} by Hipp, ...)
	doc.render();

	var buf = doc.getZip()
	             .generate({type:"nodebuffer"});
	return buf;
}

var sortWineByType = wines => {
	var white = [];
	var blush = [];
	var red = [];

	wines.forEach(wine =>{
	    if(wine.Varietal.type_color == 'White') white.push(wine);
	    if(wine.Varietal.type_color == 'Blush') blush.push(wine);
	    if(wine.Varietal.type_color == 'Red')	red.push(wine);
	});

	white = sortWineByVarietal(white);
	blush = sortWineByVarietal(blush);
	red   = sortWineByVarietal(red);



	return [{
				type : 'White',
				varietals : sortPrice(white)
			},
			{
				type : 'Blush',
				varietals : sortPrice(blush)
			},
			{
				type : 'Red',
				varietals : sortPrice(red)
			}];

}

var sortPrice = wines => {
  wines.forEach(wine => {
    wine.vw.sort((a,b) => {return a.item_price>=b.item_price;});
  })
  return wines;
}


var sortWineByVarietal = wines => {
	var filteredWines = [];
  wines = wines.sort((a, b) => {return a.Varietal.specID-b.Varietal.specID});

	let matchedGroup;
	wines.forEach(wine => {
		if(!wine.quantity > 0) return;
		let match = filteredWines.filter(group => {
			return group.type_name === wine.Varietal.type_name;
		})[0];
		if (!match) {
			matchedGroup = { type_name: wine.Varietal.type_name, vw: [] };
			filteredWines.push(matchedGroup);
		} else matchedGroup = match;
    
		matchedGroup.vw.push({year : wine.item_year == 0 ? 'NV' : wine.item_year,
									               producer : wine.Vineyard.name_text ? wine.Vineyard.name_text : '',
									 	                 name : wine.item_name_desc ? '- '+wine.item_name_desc : '',
							           wine_description : wine.item_description ? wine.item_description.replace(re, '') : '',
                                      bin : wine.bin ? '[BIN '+ wine.bin +']' : '',
							 	                       gp : wine.glass_price ? '$'+wine.glass_price : '',
							 	                       ip : wine.item_price ? '$'+wine.item_price : '',
                               item_price : wine.item_price});

	});
	return filteredWines;
}

