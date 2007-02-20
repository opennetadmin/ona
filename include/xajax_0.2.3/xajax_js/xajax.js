function Xajax(){this.DebugMessage=function(text){if(text.length > 1000)text=text.substr(0,1000)+"...\n[long response]\n...";try{if(this.debugWindow==undefined||this.debugWindow.closed==true){this.debugWindow=window.open('about:blank','xajax-debug','width=800,height=600,scrollbars=1,resizable,status');this.debugWindow.document.write('<html><head><title>Xajax debug output</title></head><body><h2>Xajax debug output</h2><div id="debugTag"></div></body></html>');}
text=text.replace(/&/g,"&amp;")
text=text.replace(/</g,"&lt;")
text=text.replace(/>/g,"&gt;")
debugTag=this.debugWindow.document.getElementById('debugTag');debugTag.innerHTML=('<b>'+(new Date()).toString()+'</b>: '+text+'<hr/>')+debugTag.innerHTML;}catch(e){alert("Xajax Debug:\n "+text);}
};this.workId='xajaxWork'+new Date().getTime();this.depth=0;this.getRequestObject=function(){if(xajaxDebug)this.DebugMessage("Initializing Request Object..");var req=null;if(typeof XMLHttpRequest!="undefined")
req=new XMLHttpRequest();if(!req&&typeof ActiveXObject!="undefined"){try{req=new ActiveXObject("Msxml2.XMLHTTP");}
catch(e){try{req=new ActiveXObject("Microsoft.XMLHTTP");}
catch(e2){try{req=new ActiveXObject("Msxml2.XMLHTTP.4.0");}
catch(e3){req=null;}
}
}
}
if(!req&&window.createRequest)
req=window.createRequest();if(!req)this.DebugMessage("Request Object Instantiation failed.");return req;}
this.$=function(sId){if(!sId){return null;}
var returnObj=document.getElementById(sId);if(!returnObj&&document.all){returnObj=document.all[sId];}
if(xajaxDebug&&!returnObj&&sId!=this.workId){this.DebugMessage("Element with the id \""+sId+"\" not found.");}
return returnObj;}
this.include=function(sFileName){var objHead=document.getElementsByTagName('head');var objScript=document.createElement('script');objScript.type='text/javascript';objScript.src=sFileName;objHead[0].appendChild(objScript);}
this.stripOnPrefix=function(sEventName){sEventName=sEventName.toLowerCase();if(sEventName.indexOf('on')==0){sEventName=sEventName.replace(/on/,'');}
return sEventName;}
this.addOnPrefix=function(sEventName){sEventName=sEventName.toLowerCase();if(sEventName.indexOf('on')!=0){sEventName='on'+sEventName;}
return sEventName;}
this.addHandler=function(sElementId,sEvent,sFunctionName){if(window.addEventListener){sEvent=this.stripOnPrefix(sEvent);eval("this.$('"+sElementId+"').addEventListener('"+sEvent+"',"+sFunctionName+",false);");}
else{sAltEvent=this.addOnPrefix(sEvent);eval("this.$('"+sElementId+"').attachEvent('"+sAltEvent+"',"+sFunctionName+",false);");}
}
this.removeHandler=function(sElementId,sEvent,sFunctionName){if(window.addEventListener){sEvent=this.stripOnPrefix(sEvent);eval("this.$('"+sElementId+"').removeEventListener('"+sEvent+"',"+sFunctionName+",false);");}
else{sAltEvent=this.addOnPrefix(sEvent);eval("this.$('"+sElementId+"').detachEvent('"+sAltEvent+"',"+sFunctionName+",false);");}
}
this.create=function(sParentId,sTag,sId){var objParent=this.$(sParentId);objElement=document.createElement(sTag);objElement.setAttribute('id',sId);if(objParent)
objParent.appendChild(objElement);}
this.insert=function(sBeforeId,sTag,sId){var objSibling=this.$(sBeforeId);objElement=document.createElement(sTag);objElement.setAttribute('id',sId);objSibling.parentNode.insertBefore(objElement,objSibling);}
this.insertAfter=function(sAfterId,sTag,sId){var objSibling=this.$(sAfterId);objElement=document.createElement(sTag);objElement.setAttribute('id',sId);objSibling.parentNode.insertBefore(objElement,objSibling.nextSibling);}
this.getInput=function(sType,sName,sId){var Obj;if(sType=="radio"&&!window.addEventListener){Obj=document.createElement('<input type="radio" id="'+sId+'" name="'+sName+'">');}
else{Obj=document.createElement('input');Obj.setAttribute('type',sType);Obj.setAttribute('name',sName);Obj.setAttribute('id',sId);}
return Obj;}
this.createInput=function(sParentId,sType,sName,sId){var objParent=this.$(sParentId);var objElement=this.getInput(sType,sName,sId);if(objParent&&objElement)
objParent.appendChild(objElement);}
this.insertInput=function(sBeforeId,sType,sName,sId){var objSibling=this.$(sBeforeId);var objElement=this.getInput(sType,sName,sId);if(objElement&&objSibling&&objSibling.parentNode)
objSibling.parentNode.insertBefore(objElement,objSibling);}
this.remove=function(sId){objElement=this.$(sId);if(objElement&&objElement.parentNode&&objElement.parentNode.removeChild){objElement.parentNode.removeChild(objElement);}
}
this.replace=function(sId,sAttribute,sSearch,sReplace){var bFunction=false;if(sAttribute=="innerHTML")
sSearch=this.getBrowserHTML(sSearch);eval("var txt=this.$('"+sId+"')."+sAttribute);if(typeof txt=="function"){txt=txt.toString();bFunction=true;}
if(txt.indexOf(sSearch)>-1){var newTxt='';while(txt.indexOf(sSearch)>-1){x=txt.indexOf(sSearch)+sSearch.length+1;newTxt+=txt.substr(0,x).replace(sSearch,sReplace);txt=txt.substr(x,txt.length-x);}
newTxt+=txt;if(bFunction){eval('this.$("'+sId+'").'+sAttribute+'=newTxt;');}
else if(this.willChange(sId,sAttribute,newTxt)){eval('this.$("'+sId+'").'+sAttribute+'=newTxt;');}
}
}
this.getFormValues=function(frm){var objForm;var submitDisabledElements=false;if(arguments.length > 1&&arguments[1]==true)
submitDisabledElements=true;var prefix="";if(arguments.length > 2)
prefix=arguments[2];if(typeof(frm)=="string")
objForm=this.$(frm);else
objForm=frm;var sXml="<xjxquery><q>";if(objForm&&objForm.tagName=='FORM'){var formElements=objForm.elements;for(var i=0;i < formElements.length;i++){if(!formElements[i].name)
continue;if(formElements[i].name.substring(0,prefix.length)!=prefix)
continue;if(formElements[i].type&&(formElements[i].type=='radio'||formElements[i].type=='checkbox')&&formElements[i].checked==false)
continue;if(formElements[i].disabled&&formElements[i].disabled==true&&submitDisabledElements==false)
continue;var name=formElements[i].name;if(name){if(sXml!='<xjxquery><q>')
sXml+='&';if(formElements[i].type=='select-multiple'){for(var j=0;j < formElements[i].length;j++){if(formElements[i].options[j].selected==true)
sXml+=name+"="+encodeURIComponent(formElements[i].options[j].value)+"&";}
}
else{sXml+=name+"="+encodeURIComponent(formElements[i].value);}
}
}
}
sXml+="</q></xjxquery>";return sXml;}
this.objectToXML=function(obj){var sXml="<xjxobj>";for(i in obj){try{if(i=='constructor')
continue;if(obj[i]&&typeof(obj[i])=='function')
continue;var key=i;var value=obj[i];if(value&&typeof(value)=="object"&&this.depth <=50){this.depth++;value=this.objectToXML(value);this.depth--;}
sXml+="<e><k>"+key+"</k><v>"+value+"</v></e>";}
catch(e){if(xajaxDebug)this.DebugMessage(e.name+": "+e.message);}
}
sXml+="</xjxobj>";return sXml;}
this.loadingFunction=function(){};this.doneLoadingFunction=function(){};var loadingTimeout;this.call=function(sFunction,aArgs,sRequestType){var i,r,postData;if(document.body&&xajaxWaitCursor)
document.body.style.cursor='wait';if(xajaxStatusMessages==true)window.status='Sending Request...';clearTimeout(loadingTimeout);loadingTimeout=setTimeout("xajax.loadingFunction();",400);if(xajaxDebug)this.DebugMessage("Starting xajax...");if(sRequestType==null){var xajaxRequestType=xajaxDefinedPost;}
else{var xajaxRequestType=sRequestType;}
var uri=xajaxRequestUri;var value;switch(xajaxRequestType){case xajaxDefinedGet:{var uriGet=uri.indexOf("?")==-1?"?xajax="+encodeURIComponent(sFunction):"&xajax="+encodeURIComponent(sFunction);if(aArgs){for(i=0;i<aArgs.length;i++){value=aArgs[i];if(typeof(value)=="object")
value=this.objectToXML(value);uriGet+="&xajaxargs[]="+encodeURIComponent(value);}
}
uriGet+="&xajaxr="+new Date().getTime();uri+=uriGet;postData=null;}break;case xajaxDefinedPost:{postData="xajax="+encodeURIComponent(sFunction);postData+="&xajaxr="+new Date().getTime();if(aArgs){for(i=0;i <aArgs.length;i++){value=aArgs[i];if(typeof(value)=="object")
value=this.objectToXML(value);postData=postData+"&xajaxargs[]="+encodeURIComponent(value);}
}
}break;default:
alert("Illegal request type: "+xajaxRequestType);return false;break;}
r=this.getRequestObject();if(!r)return false;r.open(xajaxRequestType==xajaxDefinedGet?"GET":"POST",uri,true);if(xajaxRequestType==xajaxDefinedPost){try{r.setRequestHeader("Method","POST "+uri+" HTTP/1.1");r.setRequestHeader("Content-Type","application/x-www-form-urlencoded");}
catch(e){alert("Your browser does not appear to  support asynchronous requests using POST.");return false;}
}
r.onreadystatechange=function(){if(r.readyState!=4)
return;if(r.status==200){if(xajaxDebug)xajax.DebugMessage("Received:\n"+r.responseText);if(r.responseXML&&r.responseXML.documentElement)
xajax.processResponse(r.responseXML);else{var errorString="Error: the XML response that was returned from the server is invalid.";errorString+="\nReceived:\n"+r.responseText;trimmedResponseText=r.responseText.replace(/^\s+/g,"");trimmedResponseText=trimmedResponseText.replace(/\s+$/g,"");if(trimmedResponseText!=r.responseText)
errorString+="\nYou have whitespace in your response.";alert(errorString);document.body.style.cursor='default';if(xajaxStatusMessages==true)window.status='Invalid XML response error';}
}
else{var errorString="Error: the server returned the following HTTP status: "+r.status;errorString+="\nReceived:\n"+r.responseText;alert(errorString);document.body.style.cursor='default';if(xajaxStatusMessages==true)window.status='Invalid XML response error';}
delete r;r=null;}
if(xajaxDebug)this.DebugMessage("Calling "+sFunction+" uri="+uri+" (post:"+postData+")");r.send(postData);if(xajaxStatusMessages==true)window.status='Waiting for data...';delete r;return true;}
this.getBrowserHTML=function(html){tmpXajax=this.$(this.workId);if(!tmpXajax){tmpXajax=document.createElement("div");tmpXajax.setAttribute('id',this.workId);tmpXajax.style.display="none";tmpXajax.style.visibility="hidden";document.body.appendChild(tmpXajax);}
tmpXajax.innerHTML=html;var browserHTML=tmpXajax.innerHTML;tmpXajax.innerHTML='';return browserHTML;}
this.willChange=function(element,attribute,newData){if(!document.body){return true;}
if(attribute=="innerHTML"){newData=this.getBrowserHTML(newData);}
elementObject=this.$(element);if(elementObject){var oldData;eval("oldData=this.$('"+element+"')."+attribute);if(newData!==oldData)
return true;}
return false;}
this.viewSource=function(){return "<html>"+document.getElementsByTagName("HTML")[0].innerHTML+"</html>";}
this.processResponse=function(xml){clearTimeout(loadingTimeout);this.doneLoadingFunction();if(xajaxStatusMessages==true)window.status='Processing...';var tmpXajax=null;xml=xml.documentElement;if(xml==null)
return;for(var i=0;i<xml.childNodes.length;i++){if(xml.childNodes[i].nodeName=="cmd"){var cmd;var id;var property;var data;var search;var type;var before;for(var j=0;j<xml.childNodes[i].attributes.length;j++){if(xml.childNodes[i].attributes[j].name=="n"){cmd=xml.childNodes[i].attributes[j].value;}
else if(xml.childNodes[i].attributes[j].name=="t"){id=xml.childNodes[i].attributes[j].value;}
else if(xml.childNodes[i].attributes[j].name=="p"){property=xml.childNodes[i].attributes[j].value;}
else if(xml.childNodes[i].attributes[j].name=="c"){type=xml.childNodes[i].attributes[j].value;}
}
if(xml.childNodes[i].childNodes.length > 1&&xml.childNodes[i].firstChild.nodeName=="#cdata-section"){data="";for(var j=0;j<xml.childNodes[i].childNodes.length;j++){data+=xml.childNodes[i].childNodes[j].data;}
}
else if(xml.childNodes[i].childNodes.length > 1){for(var j=0;j<xml.childNodes[i].childNodes.length;j++){if(xml.childNodes[i].childNodes[j].childNodes.length > 1&&xml.childNodes[i].childNodes[j].firstChild.nodeName=="#cdata-section"){var internalData="";for(var k=0;k<xml.childNodes[i].childNodes[j].childNodes.length;k++){internalData+=xml.childNodes[i].childNodes[j].childNodes[k].nodeValue;}
}else{var internalData=xml.childNodes[i].childNodes[j].firstChild.nodeValue;}
if(xml.childNodes[i].childNodes[j].nodeName=="s"){search=internalData;}
if(xml.childNodes[i].childNodes[j].nodeName=="r"){data=internalData;}
}
}
else if(xml.childNodes[i].firstChild)
data=xml.childNodes[i].firstChild.nodeValue;else
data="";var objElement=this.$(id);var cmdFullname;try{if(cmd=="al"){cmdFullname="addAlert";alert(data);}
else if(cmd=="js"){cmdFullname="addScript/addRedirect";eval(data);}
else if(cmd=="in"){cmdFullname="addIncludeScript";this.include(data);}
else if(cmd=="as"){cmdFullname="addAssign/addClear";if(this.willChange(id,property,data)){eval("objElement."+property+"=data;");}
}
else if(cmd=="ap"){cmdFullname="addAppend";eval("objElement."+property+"+=data;");}
else if(cmd=="pp"){cmdFullname="addPrepend";eval("objElement."+property+"=data+objElement."+property);}
else if(cmd=="rp"){cmdFullname="addReplace";this.replace(id,property,search,data)
}
else if(cmd=="rm"){cmdFullname="addRemove";this.remove(id);}
else if(cmd=="ce"){cmdFullname="addCreate";this.create(id,data,property);}
else if(cmd=="ie"){cmdFullname="addInsert";this.insert(id,data,property);}
else if(cmd=="ia"){cmdFullname="addInsertAfter";this.insertAfter(id,data,property);}
else if(cmd=="ci"){cmdFullname="addCreateInput";this.createInput(id,type,data,property);}
else if(cmd=="ii"){cmdFullname="addInsertInput";this.insertInput(id,type,data,property);}
else if(cmd=="ev"){cmdFullname="addEvent";property=this.addOnPrefix(property);eval("this.$('"+id+"')."+property+"= function(){"+data+";}");}
else if(cmd=="ah"){cmdFullname="addHandler";this.addHandler(id,property,data);}
else if(cmd=="rh"){cmdFullname="addRemoveHandler";this.removeHandler(id,property,data);}
}
catch(e){if(xajaxDebug)
alert("While trying to '"+cmdFullname+"' (command number "+i+"), the following error occured:\n"
+e.name+": "+e.message+"\n"
+(id&&!objElement?"Object with id='"+id+"' wasn't found.\n":""));}
delete objElement;delete cmd;delete cmdFullname;delete id;delete property;delete search;delete data;delete type;delete before;delete internalData;delete j;delete k;}
}
delete xml;delete i;document.body.style.cursor='default';if(xajaxStatusMessages==true)window.status='Done';}
}
var xajax=new Xajax();xajaxLoaded=true;