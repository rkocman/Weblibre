// Toggle function
function groupToggle(object) {
  object.children("h2").children("div").toggleClass("off");
  object.children(".inner").toggle();
}

// Input options
function inputOptions(form){
  $("#convert .input-group").hide();

  if (form != null) {
    // Toggle
    switch(form.input_format.value) {
      case 'FB2':
        $("#convert .input-fb2").show(); break;
      case 'PDF':
        $("#convert .input-pdf").show(); break;
      case 'TXT':
      case 'TXTZ':
        $("#convert .input-txt").show(); break;
    }
    // Default values  
    form.elements['structure[chapter]'].value = "//*[((name()='h1' or name()='h2') and re:test(., '\\s*((chapter|book|section|part)\\s+)|((prolog|prologue|epilogue)(\\s+|$))', 'i')) or @class = 'chapter']";
    switch(form.input_format.value) {
      default:
        form.elements['structure[page_breaks_before]'].value = "//*[name()='h1' or name()='h2']";
        break;
      case 'EPUB':
        form.elements['structure[page_breaks_before]'].value = "/";
        break;
    }
    switch(form.input_format.value) {
      default:
        form.elements['table[level1_toc]'].value = "";
        form.elements['table[level2_toc]'].value = "";
        form.elements['table[level3_toc]'].value = "";
        break;
      case 'FB2':
        form.elements['table[level1_toc]'].value = "//h:h1";
        form.elements['table[level2_toc]'].value = "//h:h2";
        form.elements['table[level3_toc]'].value = "//h:h3";
        break;
    }
  }
}

// Output options
function outputOptions(form){
  $("#convert .output-group").hide();

  if (form != null) {
    switch(form.output_format.value) {
      case 'EPUB':
        $("#convert .output-epub").show(); break;
      case 'FB2':
        $("#convert .output-fb2").show(); break;
      case 'HTMLZ':
        $("#convert .output-htmlz").show(); break;
      case 'MOBI':
        $("#convert .output-mobi").show(); break;
      case 'PDB':
        $("#convert .output-pdb").show(); break;
      case 'PDF':
        $("#convert .output-pdf").show(); break;
      case 'PMLZ':
        $("#convert .output-pmlz").show(); break;
      case 'RB':
        $("#convert .output-rb").show(); break;
      case 'SNB':
        $("#convert .output-snb").show(); break;
      case 'TXT':
      case 'TXTZ':
        $("#convert .output-txt").show(); break;
    }
  }
}

$(document).ready(function(){
  // Add toggle select
  $("#convert h2, #convert .description")
    .html(function(index, old){
      return "<span class=\"toggle-select\">"+old+"</span>";
    });

  // Add toggle indication
  $("#convert h2").append("<div />");

  // Enable toggle
  $("#convert .toggle-select, #convert h2 div")
    .click(function(event){
      groupToggle($(event.target).parent().parent());
    });

  // After start actions
  groupToggle($("#convert .group"));
  groupToggle($("#convert .metadata"));
  inputOptions(self.document.forms[0]);
  outputOptions(self.document.forms[0]);
});