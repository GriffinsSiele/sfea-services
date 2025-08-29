var SUCCESS_RESPONSE_CODES = [3, 14];
var CHECK_STATUS_COUNTER_DEFAULT_VALUE = DKBM_PROCESSING_TIME / DKBM_RESPONSE_CHECK_TIME;
var PROCESSING_CODE = 13;

var policyInfoStatusCounter = CHECK_STATUS_COUNTER_DEFAULT_VALUE;

$(document).ready(function () {
    initialReload();

    $('#check').on("change", function (event) {
        clearAllTextInputsOnForm("driverBlock");
        if ($('#check').is(":checked")) {
            $('#driverBlock').addClass("visible");
            disableFindIfNeeded();
        } else {
            $('#driverBlock').removeClass("visible");
            $('[id^="patronymicExists"]').prop("checked", false);
            $('#confirmCheck').prop("checked", false);
            $("#buttonFind").prop('disabled', false);
        }
    });

    $('#confirmCheck').on("change", function (event) {
        disableFindIfNeeded();
    });


    $('[id^="patronymicExists"]').each(function () {
        $(this).on("change", function (event) {
            var patronymicInputId = this.id.replace("Exists", "")
            $('#' + patronymicInputId).val('');
            disableInput(patronymicInputId, $(this).is(":checked"));
        })
    });

    $('#vin').on('input propertychange', function (event) {
        this.value = this.value.toLocaleUpperCase();
    });
    $('#licensePlate').on('input propertychange', function (event) {
        this.value = this.value.toLocaleUpperCase();
    });

    $('#bodyNumber').on('input propertychange', function (event) {
        this.value = this.value.toLocaleUpperCase();
    });
    $('#chassisNumber').on('input propertychange', function (event) {
        this.value = this.value.toLocaleUpperCase();
    });

    $("#requestDate").datetimepicker({
        minDate: "1900/01/02",
        maxDate: new Date(),
        yearStart: 1900,
        value: new Date()
    });

    $("[id^='birthday']").each(function () {
        $(this).datetimepicker({
            minDate: "1900/01/02",
            maxDate: new Date(),
            yearStart: 1900,
            value: new Date()
        })
    });

    $("#tsBlock input").keyup(function () {
        var elem = $(this)[0];
        if (!$(elem).is('[readonly]')) {
            if (elem.value) {
                disableTSEmptyInputs();
            } else {
                enableTSInputs();
            }
        }
    });
});

function disableFindIfNeeded() {
    if (allowAcceptForm()) {
        $("#buttonFind").prop('disabled', false);
    } else {
        $("#buttonFind").prop('disabled', true);
    }
}

function disableTSEmptyInputs(){
    $('#licensePlate').attr('placeholder', '');
    $("#tsBlock input").each(function (idx, el) {
        if (!el.value) {
            disableInput(el.id, true);
        }
    });
}

function enableTSInputs() {
    $('#licensePlate').attr('placeholder', 'В000АА00');
    $("#tsBlock input").each(function (idx, el) {
        disableInput(el.id, false);
    });
}

function showError(error) {
    var errorMessagesDiv = $("#errorMessages");
    errorMessagesDiv.html("Ошибка!" + "<br/>" + error + "<br/>");
    errorMessagesDiv.show();
}

function clearErrors() {
    var errorMessagesDiv = $("#errorMessages");
    errorMessagesDiv.html("");
    errorMessagesDiv.hide();

    $('.emptyFieldMsg').hide();
    $('.notValid').each(function() {
        $(this).removeClass("notValid")
    });
}

function sendRequest(publicKey) {
    if (!validate()) {
        return;
    }

    LoadingImage.show();

    getRecaptchaToken(publicKey, makeAjaxCall, errorCapture);
}

function makeAjaxCall(token) {
    var formData = getFormDataParams(token);

    $.ajax({
        type: "post",
        cache: false,
        url: "policyInfo.htm",
        data: formData,
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Accept", "application/json");
        },
        success: function (data) {
            if (!data.validCaptcha) {
                LoadingImage.hide();
                showError(data.errorMessage);
                return;
            } else if(data.invalidFields && data.invalidFields.length > 0) {
                LoadingImage.hide();
                showErrors(null, data.invalidFields);
                return;
            }
            if (data.errorMessage && data.errorMessage !== "") {
                LoadingImage.hide();
                showError(data.errorMessage);
                return;
            }
            $("#processId").val(data.processId);
            setTimeout(startGetPolicyStatusJob, DKBM_RESPONSE_CHECK_TIME, data.processId);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            LoadingImage.hide();
            resetCaptch();
            if (jqXHR.status === '504') {
                showError("Превышено время ожидания ответа от сервера очередей сообщений");
            } else {
                showError("Произошла непредвиденная ошибка. Попробуйте еще раз.");
            }
        }
    });
}

function errorCapture(err) {
    showError('Не удалось получить токен доступа');
    LoadingImage.hide();
}

function startGetPolicyStatusJob(processId) {
    $.ajax({
        type: "GET",
        url: "checkPolicyInfoStatus.htm?processId=" + processId,
        cache: false,
        success: function (data) {
            policyInfoStatusCounter--;
            var statusCode = data.RequestStatusInfo.RequestStatusCode;
            if (policyInfoStatusCounter >= 0 && statusCode === PROCESSING_CODE) {
                setTimeout(startGetPolicyStatusJob, DKBM_RESPONSE_CHECK_TIME, processId);
            } else {
                LoadingImage.hide();
                if (SUCCESS_RESPONSE_CODES.indexOf(statusCode) >= 0) {
                    deleteInvisibleDrivers();
                    $("#requestForm").submit();
                } else {
                    setAutoCanceledStatus(processId);
                    showError("Сервис временно недоступен");
                }
            }
        },
        error: function (jqXHR) {
            LoadingImage.hide();
            showError("Произошла непредвиденная ошибка. Попробуйте еще раз.");
        }
    });
}

function setAutoCanceledStatus(processId) {
    $.ajax({
        type: "GET",
        url: "setAutoCanceledStatus.htm?processId=" + processId,
        cache: false,
        success: function (data) {},
        error: function (jqXHR) {}
    });
}

function deleteInvisibleDrivers() {
    for(var i = 1; i < 4; ++i) {
        if (!isVisibleDriveRow(i)) {
            $("#driver_" + i).remove();
        }
    }
}

function getFormDataParams(token) {
    return 'bsoseries=' + encodeURIComponent($("#bsoseries").val()) + '&' +
        'bsonumber=' + encodeURIComponent($("#bsonumber").val()) + '&' +
        'requestDate=' + $("#requestDate").val() + '&' +
        'vin=' + encodeURIComponent($("#vin").val()) + '&' +
        'licensePlate=' + encodeURIComponent($("#licensePlate").val()) + '&' +
        'bodyNumber=' + encodeURIComponent($("#bodyNumber").val()) + '&' +
        'chassisNumber=' + encodeURIComponent($("#chassisNumber").val()) + '&' +
        'isBsoRequest=' + $('#bsoBlockTab').hasClass('active') + '&' +
        '&captcha=' + token;
}

function validate() {
    clearErrors();
    var requiredFields = [];
    var invalidFields = [];

    var isBsoTabActive = $('#bsoBlockTab').hasClass('active');

    requiredFields.push('requestDate');
    if (isBsoTabActive) {
        requiredFields.push('bsoseries', 'bsonumber');

        var bsoseries = $('#bsoseries').val();
        if (!isEmpty(bsoseries) && bsoseries.length > 6) {
            invalidFields.push('bsoseries');
        }
        var bsonumber = $('#bsonumber').val();
        var intRegex = /^\d+$/;
        if (!isEmpty(bsonumber) && (bsonumber.length !== 10 || !intRegex.test(bsonumber))) {
            invalidFields.push('bsonumber');
        }
    } else {
        var vinVal = $('#vin').val();
        var licensePlateVal = $('#licensePlate').val();
        var chassisNumberVal = $('#chassisNumber').val();
        var bodyNumberVal = $('#bodyNumber').val();
        var hasAnyValue = !isEmpty(vinVal) || !isEmpty(licensePlateVal) || !isEmpty(bodyNumberVal)
            || !isEmpty(chassisNumberVal);
        if (!hasAnyValue) {
            requiredFields.push('vin', 'licensePlate', 'bodyNumber', 'chassisNumber');
        }
        if (!isEmpty(vinVal) && (vinVal.length > 17 || !checkVinFormat(vinVal))) {
            invalidFields.push('vin');
        }
        if (!isEmpty(licensePlateVal) && !checkLicensePlateFormat(licensePlateVal)) {
            invalidFields.push('licensePlate');
        }
        if (!isEmpty(chassisNumberVal) && chassisNumberVal.length > 100) {
            invalidFields.push('chassisNumber');
        }
        if (!isEmpty(bodyNumberVal) && bodyNumberVal.length > 100) {
            invalidFields.push('bodyNumber');
        }
    }

    var requestDate = $("#requestDate").val();
    if (requestDate &&
        (!checkDateFormat(requestDate)
            || asMoment(requestDate).isAfter(tomorrow())
            || beforeMinDate(requestDate)
    )) {
        invalidFields.push('requestDate');
    }

    if ($('#check').is(":checked")) {
        addToValidation(requiredFields, invalidFields, 0, requestDate);
        if (isVisibleDriveRow(1)) {
            addToValidation(requiredFields, invalidFields, 1, requestDate);
        }
        if (isVisibleDriveRow(2)) {
            addToValidation(requiredFields, invalidFields, 2, requestDate);
        }
        if (isVisibleDriveRow(3)) {
            addToValidation(requiredFields, invalidFields, 3, requestDate);
        }
    }

    var emptyInvalidFields = validateEmptyFields(requiredFields);
    showErrors(emptyInvalidFields, invalidFields);

    return emptyInvalidFields.length === 0 && invalidFields.length === 0;
}

function validateEmptyFields(requiredFields) {
    var validateFields = [];
    $.each(requiredFields, function (index, requiredField) {
        var f = $("#" + requiredField)[0];
        if (isEmpty(f.value)) {
            validateFields.push(requiredField);
        }
    });

    return validateFields;
}

function showErrors(emptyElements, invalidFields) {
    if (emptyElements && emptyElements.length > 0) {
        setNotValidStyle(emptyElements, 'Не заполнено обязательное поле');
    }

    if (invalidFields && invalidFields.length > 0) {
        setNotValidStyle(invalidFields, 'Некорректно указаны данные');
    }
}

function tomorrow() {
    return moment().add(1, 'days').startOf('day');
}

function beforeMinDate(requestDate) {
    var reqDAte = asMoment(requestDate);
    var minDate = moment('02.01.1900', 'DD.MM.YYYY').startOf('day');
    return reqDAte.isBefore(minDate);
}

function checkLicensePlateFormat(licensePlate) {
    console.log(licensePlate)
    var pattern = new RegExp(/^[A-ZА-ЯЁ0-9]{0,20}$/);
    return pattern.test(licensePlate);
}

function addToValidation(requiredFields, validatedFields, idx, requestDate) {
    var birthday = indexed('birthday', idx);
    var name = indexed('name', idx);
    var driverNumber = indexed('driverNumber', idx);
    var driverSerial = indexed('driverSerial', idx);
    var patronymicExists = indexed('patronymicExists', idx);
    var patronymic = indexed('patronymic', idx);

    requiredFields.push(birthday, name, driverNumber, driverSerial);
    if (!$('#' + patronymicExists).is(":checked")) {
        requiredFields.push(patronymic);
    }
    var birthdayValue = $("#" + birthday).val();
    if (!isEmpty(birthdayValue)
            && (!checkDateFormat(birthdayValue)
                || !birthdayBeforeRequestDate(birthdayValue, requestDate)
                || !diffLessThan150Years(birthdayValue, requestDate))) {
        validatedFields.push(birthday);
    }
}

function birthdayBeforeRequestDate(birthday, requestDate) {
    if (birthday && requestDate) {
        var b = asMoment(birthday);
        var r = asMoment(requestDate);
        return b.isBefore(r);
    }
    return false;
}

function asMoment(stringDateValue) {
    return moment(stringDateValue, 'DD.MM.YYYY');
}

function diffLessThan150Years(birthday, requestDate) {
    if (birthday && requestDate) {
        var b = asMoment(birthday);
        var r = asMoment(requestDate);
        return r.diff(b, 'years', true) <= 150;
    }
    return false;
}

function indexed(fieldId, idx) {
    return fieldId + idx;
}

function checkVinFormat(vin){
    var pattern = new RegExp(/^[a-zA-Z0-9]*$/);
    return pattern.test(vin);
}

function checkDateFormat(date) {
    var pattern = new RegExp(/^\d{2}[.]\d{2}[.]\d{4}$/);
    return pattern.test(date);
}

function setNotValidStyle(invalidFields, msg) {
    $.each(invalidFields, function (index, invalidField) {
        $('#' + invalidField).addClass("notValid");
        $("label[for='" + invalidField + "']").addClass("notValid")
            .last().html(msg).show();
    });
}

function disableInput(inputId, isDisabled) {
    if (isDisabled) {
        $("label[for='" + inputId + "']").addClass("isDisabled");
        $("#" + inputId).prop('readonly', true).addClass("isDisabled");
    } else {
        $("label[for='" + inputId + "']").removeClass("isDisabled");
        $("#" + inputId).prop('readonly', false).removeClass("isDisabled");
    }
}

function initialReload() {
    setActiveTabPage("bsoBlock");
    reloadInput();
}

function reloadInput() {
    clearErrors();
    $("#buttonFind").prop('disabled', true);

    $(".more-info-text").removeClass("visible");
    $("#driverBlock").removeClass("visible");

    hideDriverRow(1);
    hideDriverRow(2);
    hideDriverRow(3);

    clearAllTextInputsOnForm("driverBlock");
    clearAllTextInputsOnForm("policyBlock");
    enableTSInputs();
    $('[id^="patronymic"]').each(function () {
        if ($(this).is(':checkbox')) {
            $(this).prop("checked", false)
        } else {
            disableInput(this.id, false)
        }
    });
    $('#confirmCheck').prop("checked", false);
    $('#check').prop("checked", false);
    $('#requestDate').val(moment().format('DD.MM.YYYY'));

    disableFindIfNeeded();
}

function addDriverRow() {
    if (!isVisibleDriveRow(1)) {
        showDriverRow(1);
    } else if (!isVisibleDriveRow(2)) {
        showDriverRow(2);
    } else if (!isVisibleDriveRow(3)) {
        showDriverRow(3);
        hideAddDriverButton();
    }
}

function isVisibleDriveRow(rowNum) {
    return $('#driver_' + rowNum).hasClass("visible");
}

function showDriverRow(rowNum) {
    if (!isVisibleDriveRow(rowNum)) {
        $("#driver_" + rowNum).addClass("visible");
        if (rowNum > 0) {
            $("#separatorLine_" + rowNum).addClass("visible");
        }
    }
}

function hideDriverRow(rowNum) {
    if (isVisibleDriveRow(rowNum)) {
        $("#driver_" + rowNum).removeClass("visible");
        if (rowNum > 0) {
            $("#separatorLine_" + rowNum).removeClass("visible");
        }
    }
}

function showAddDriverButton() {
    $(".add-block").show();
}

function hideAddDriverButton() {
    $(".add-block").hide();
}

function deleteDriverRow(rowNum) {
    var lastVisibleRowNum = getLastVisibleDriverRowNum();
    if (rowNum !== lastVisibleRowNum) {
        for (let i = rowNum; i < lastVisibleRowNum; i++) {
            copyDriver(i);
        }
    }

    clearDriver(lastVisibleRowNum);
    hideDriverRow(lastVisibleRowNum);
    if (!isVisibleDriveRow(3)) {
        showAddDriverButton();
    }
}

function copyDriver(rowNum) {
    var nextRowNum = rowNum + 1;
    $("#surname" + rowNum).val($("#surname" + nextRowNum).val());
    $("#name" + rowNum).val($("#name" + nextRowNum).val());
    $("#patronymic" + rowNum).val($("#patronymic" + nextRowNum).val());

    var needToSetCheckbox = $("#patronymicExists" + nextRowNum).is(":checked");
    disableInput("patronymic" + rowNum, needToSetCheckbox);
    $("#patronymicExists" + rowNum).prop("checked", needToSetCheckbox);

    $("#birthday" + rowNum).val($("#birthday" + nextRowNum).val());
    $("#driverSerial" + rowNum).val($("#driverSerial" + nextRowNum).val());
    $("#driverNumber" + rowNum).val($("#driverNumber" + nextRowNum).val());
}

function clearDriver(rowNum) {
    $("#surname" + rowNum).val('');
    $("#name" + rowNum).val('');
    $("#patronymic" + rowNum).val('');
    disableInput("patronymic" + rowNum, false);
    $("#patronymicExists" + rowNum).prop("checked", false);
    $("#birthday" + rowNum).val('');
    $("#driverSerial" + rowNum).val('');
    $("#driverNumber" + rowNum).val('');
}

function getLastVisibleDriverRowNum() {
    if (isVisibleDriveRow(3)) {
        return 3;
    } else if (isVisibleDriveRow(2)) {
        return 2;
    } else if (isVisibleDriveRow(1)) {
        return 1;
    } else {
        return 0;
    }
}

function switchTab(tabBlockId) {
    setActiveTabPage(tabBlockId);

    if ($("#tsBlock").hasClass("active")) {
        enableTSInputs();
    }

    clearAllTextInputsOnForm("policyBlock");
}

function setActiveTabPage(newActiveBlockId) {
    var currActiveTab = $("#policyBlock .active");
    var currActiveTabId = currActiveTab.attr('id');
    var currActiveBlockId = currActiveTabId.replace(/Tab$/, '');
    if (currActiveBlockId  == newActiveBlockId) {
        return;
    }

    currActiveTab.removeClass("active").addClass("inactive")
        .wrap("<a class=\"tab-link\" onclick=\"switchTab('" + currActiveBlockId + "')\"></a>");
    $("#" + currActiveBlockId).removeClass("active").addClass("inactive");
    var newActiveTabId = newActiveBlockId + "Tab";
    $("#" + newActiveTabId).removeClass("inactive").addClass("active").unwrap();
    $("#" + newActiveBlockId).removeClass("inactive").addClass("active");
}

var toggleMoreInfoText = function (elem) {
    var targetElem = $(elem).next();
    if (targetElem && targetElem.hasClass('more-info-text')) {
        targetElem.addClass("visible");
    }
};

var closeMoreInfoText = function (elem) {
    var targetElem = $(elem).next();
    if (targetElem && targetElem.hasClass('more-info-text')) {
        targetElem.removeClass("visible");
    }
};

function captchaCallback() {
    disableFindIfNeeded();
}

function allowAcceptForm() {
    return ($('#confirmCheck').is(":checked") || !$('#check').is(":checked"));
}