function goBack() {
    window.location.href = "policyInfo.htm"
}

function saveResult(processId) {
    LoadingImage.show();
    $.ajax({
        type: "post",
        url: "save_policy_info.htm",
        cache: false,
        data: $("#pdfForm").serialize(),
        success: function (response, status, xhr) {
            LoadingImage.hide();
            var filename = "Запрос_№" + processId + ".pdf";
            var binaryString = window.atob(response);
            var binaryLen = binaryString.length;
            var bytes = new Uint8Array(binaryLen);
            for (let i = 0; i < binaryLen; i++) {
                let ascii = binaryString.charCodeAt(i);
                bytes[i] = ascii;
            }
            var blob = new Blob([bytes], {type: "application/pdf"});
            if (typeof window.navigator.msSaveBlob !== "undefined") {
                window.navigator.msSaveBlob(blob, filename);
            } else {
                var URL = window.URL || window.webkitURL;
                var downloadUrl = URL.createObjectURL(blob);
                if (filename) {
                    var a = document.createElement("a");
                    if (typeof a.download === "undefined") {
                        window.location = downloadUrl;
                    } else {
                        a.href = downloadUrl;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                    }
                } else {
                    window.location = downloadUrl;
                }
                setTimeout(function () {
                    URL.revokeObjectURL(downloadUrl);
                }, 100);
            }
        },
        error: function (data, errorThrown) {
            LoadingImage.hide();
            showError("Ошибка сохранение отчета по запросу №" + processId);
        }
    });
}

function showError(error) {
    var errorMessagesDiv = $("#errorMessages");
    errorMessagesDiv.html("Ошибка!" + "<br/>" + error + "<br/>");
    errorMessagesDiv.show();
}