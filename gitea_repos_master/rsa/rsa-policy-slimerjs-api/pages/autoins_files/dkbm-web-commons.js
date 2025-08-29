/**
 * @author Dmitry Poddubnik
 * @since 18.08.14
 */

function isEmpty(str){
    return str == null || str.length === 0 || ($.trim(str).length===0);
}

function isNotEmptyArray(array){
    return array != null   &&   typeof array !== 'undefined'   &&   array.length > 0;
}

function containsOnlyNumbers(str) {
    var pattern = new RegExp("^[0-9]+$");
    return pattern.test(str);
}

function checkDateFormat(date){
    var pattern = new RegExp(/^\d{2}[.]\d{2}[.]\d{4}$/);
    return pattern.test(date);
}

function clearAllTextInputsOnForm(formId) {
    $('#' + formId + " input[type='text']").val("");
}

/**
 * вызывать в onReady !!!
 * @param inputId
 * @returns {*|jQuery|HTMLElement}
 */
function ensureInputTextOnlyNumbers(inputId) {
    var inputText = $("#" + inputId);
    inputText.keydown(function (e) {
        // Allow: backspace, delete, tab, escape, enter and .
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
            // Allow: Ctrl+A
            (e.keyCode == 65 && e.ctrlKey === true) ||
            // Allow: home, end, left, right
            (e.keyCode >= 35 && e.keyCode <= 39)) {
            // let it happen, don't do anything
            return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
    return inputText;
}

// to the end of file - ff31 crashes if not
(function($) {
    $.fn.extend( {
        limiter: function(limit, regexp) {
            $(this).on("keyup focus", function() {
                var chars = this.value.length;
                if (chars > limit) {
                    this.value = this.value.substr(0, limit);
                }
                if (regexp!=null) {
                    var str = this.value;
                    this.value = str.replace(regexp, "");
                    //console.log(str);
                }
            });
        }
    });
})(jQuery);


function showErrorBorder(errorStatusId, elements) {
    if (errorNoTs == errorStatusId) {
        $.each(elements, function (index, value) {
            $("#" + value).addClass("notValidImportant");
        });
    }
}


function hideErrorBorder(elements) {
    $.each(elements, function(index, value) {
        $("#" + value).removeClass("notValidImportant");
    });
}