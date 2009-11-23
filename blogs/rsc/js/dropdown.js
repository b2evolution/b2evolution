/************************************************************************************************************
Editable select
Copyright (C) September 2005  DTHMLGoodies.com, Alf Magne Kalleland

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

Dhtmlgoodies.com., hereby disclaims all copyright interest in this script
written by Alf Magne Kalleland.

Alf Magne Kalleland, 2006
Owner of DHTMLgoodies.com
	
************************************************************************************************************/	


// Path to arrow images
var arrowImage = 'rsc/icons/select_arrow.gif';	
var currentlyOpenedOptionBox = false;
var activeOption;		

// Create editable select 	
function createEditableSelect(dest, id)
{
	var dest = document.getElementById(dest);				
	var parent = document.getElementById('sbp_' + id);
	var div = document.getElementById('sb_' + id);
	
	if(div)
	{
		parent.removeChild(div);
		parent.appendChild(dest);
	}
	
	dest.className='selectBoxInput';
					
	div = document.createElement('DIV');
	div.id = 'sb_' + id;		
	div.style.styleFloat = 'left';
	div.style.width = dest.offsetWidth + 16 + 'px';
	div.style.position = 'relative';				
	
	parent = dest.parentNode;
	parent.id = 'sbp_' + id;		
	parent.insertBefore(div,dest);	

	div.appendChild(dest);	
	div.className='selectBox';
	div.style.zIndex = 10000;	

	var img = document.createElement('IMG');
	img.src = arrowImage;
	img.className = 'selectBoxArrow';				
	img.onclick = selectBox_showOptions;
	img.id = id;		

	div.appendChild(img);
	
	var optionDiv = document.createElement('DIV');
	optionDiv.id = 'sbo_' + id;
	optionDiv.className='selectBoxOptionContainer';
	optionDiv.style.width = div.offsetWidth-2 + 'px';		
	div.appendChild(optionDiv);
	
	if(navigator.userAgent.indexOf('MSIE')>=0){
		var iframe = document.createElement('<IFRAME src="about:blank" frameborder=0>');
		iframe.style.width = optionDiv.style.width;
		iframe.style.height = optionDiv.offsetHeight + 'px';
		iframe.style.display='none';
		iframe.id = 'sbi' + id;
		div.appendChild(iframe);
	}
	
	if(dest.getAttribute('selectBoxOptions')){
		var options = dest.getAttribute('selectBoxOptions').split(';');
		var optionsTotalHeight = 0;
		var optionArray = new Array();
		for(var no=0;no<options.length;no++){
			var anOption = document.createElement('DIV');
			anOption.innerHTML = options[no];
			anOption.className='selectBoxAnOption';
			anOption.onclick = selectOptionValue;
			anOption.style.width = optionDiv.style.width.replace('px','') - 2 + 'px'; 
			anOption.onmouseover = highlightSelectBoxOption;
			optionDiv.appendChild(anOption);	
			optionsTotalHeight = optionsTotalHeight + anOption.offsetHeight;
			optionArray.push(anOption);
		}
		if(optionsTotalHeight > optionDiv.offsetHeight){				
			for(var no=0;no<optionArray.length;no++){
				optionArray[no].style.width = optionDiv.style.width.replace('px','') - 22 + 'px'; 	
			}	
		}		
		optionDiv.style.display='none';
		optionDiv.style.visibility='visible';
	}		
}		

// Show options
function selectBox_showOptions()
{	
    showOptions(this.getAttribute('id'));				
}

// Show options by element ID
function showOptions(id)
{		
	var optionDiv = document.getElementById('sbo_' + id);
	if(optionDiv.style.display=='block'){
		optionDiv.style.display='none';
		if(navigator.userAgent.indexOf('MSIE')>=0)document.getElementById('sbi_' + id).style.display='none';				
	}else{			
		optionDiv.style.display='block';
		if(navigator.userAgent.indexOf('MSIE')>=0)document.getElementById('sbi_' + id).style.display='block';				
		if(currentlyOpenedOptionBox && currentlyOpenedOptionBox!=optionDiv)currentlyOpenedOptionBox.style.display='none';	
		currentlyOpenedOptionBox= optionDiv;			
	}
}	

// Select option value
function selectOptionValue()
{
	var parentNode = this.parentNode.parentNode;
	var textInput = parentNode.getElementsByTagName('INPUT')[0];
	textInput.value = this.innerHTML;	
	this.parentNode.style.display='none';		
	if(navigator.userAgent.indexOf('MSIE')>=0)document.getElementById('sbi_' + parentNode.id.replace(/[^\d]/g,'')).style.display='none';
	
}	

// Highlight option
function highlightSelectBoxOption()
{
	if(this.style.backgroundColor=='#316AC5'){
		this.style.backgroundColor='';
		this.style.color='';
	}else{
		this.style.backgroundColor='#316AC5';
		this.style.color='#FFF';			
	}	
	
	if(activeOption){
		activeOption.style.backgroundColor='';
		activeOption.style.color='';			
	}
	activeOption = this;		
}