if (!String.prototype.trim) {
  (function () {
    // Вырезаем BOM и неразрывный пробел
    String.prototype.trim = function () {
      return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '')
    }
  })()
}

$(document).ready(function () {
  initform()

  if (undefined !== $('#checkform').attr('data-no-ajax')) {
    return
  }

  const submitButtonDefaultText = $('#submitbutton').html()
  const spinnerIcon = $('<i class="fa fa-solid fa-spinner fa-spin"></i>')

  $('#checkform').submit(function () {
    $('#submitbutton').html('').append(spinnerIcon).append(submitButtonDefaultText).prop('disabled', true)
    $('#request').html('')
    $('#response').html('')

    $.post(location.href, $('#checkform').serialize(), function (result) {
      const mode = $('#mode').val()
      const request = $('#request', $(result)).html()
      const response = $('#response', $(result)).html()
      const url = $('#url', $(result)).val()
      const status = parseInt($('#status', $(result)).val(), 10)
      const checkform = $('#checkform', $(result)).html()

      $('#request').html(request)

      if (status === 0) {
        setTimeout(function tick () {
          $.ajax({
            url: url,
            success: function (response) {
              let status = -1

              if (mode === 'xml') {
                status = parseInt(response.documentElement.getAttribute('status'), 10)
                response = new XMLSerializer().serializeToString(response.documentElement)
                response = '<textarea class="form-control" data-ace-editor="xml">' + response + '</textarea>'
              } else {
                status = parseInt($('#status', $(response)).val(), 10)
              }

              if (status === 1) {
                $('#response').html(response)
                $('#submitbutton').html('').append(submitButtonDefaultText).prop('disabled', false)

                if (mode === 'html') {
                  $('#checkform').html(checkform)

                  initform()
                } else {
                  updateAce()
                }
              } else {
                setTimeout(tick, 3000)
              }
            },
            error: function () {
              setTimeout(tick, 3000)
            }
          })
        }, 3000)
      } else {
        $('#response').html(response)
        $('#submitbutton').html('').append(submitButtonDefaultText).prop('disabled', false)

        if (mode === 'html') {
          $('#checkform').html(checkform)

          initform()
        } else {
          updateAce()
        }
      }
    })

    return false
  })

  $('#ts').val(new Date().getTime())

  $('#thefile').change(function () {
    $('#loading').toggle()
    $('#thefile').attr('disabled', 'disabled')
    files = this.files
    var data = new FormData()
    $.each(files, function (key, value) {
      data.append(key, value)
    })
    data.append('ts', $('#ts').val())
    $.ajax({
      url: 'files.php?uploadfiles',
      type: 'POST',
      data: data,
      cache: false,
      dataType: 'json',
      processData: false,
      contentType: false,
      success: function (respond, textStatus, jqXHR) {
        if (typeof respond.error === 'undefined') {
          $('#loading').toggle()
//                    $("#thefile").removeAttr('disabled');
          $('#thefile').hide()
          $('#thefile').val('')
          $.each(respond.files, function (key, value) {
            $('#forfiles').append('Загружен файл ' + value)
            $('#filename').val(value)
            $('#filename').show()
          })
        } else {
          $('#loading').toggle()
          $('#thefile').removeAttr('disabled')
          $('#thefile').val('')
          alert('Ошибка ответа: ' + respond.error)
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $('#loading').toggle()
        $('#thefile').removeAttr('disabled')
        $('#thefile').val('')
        alert('Ошибка передачи: ' + errorThrown)
      }
    })
  })
})

function updateAce () {
  document.querySelectorAll('textarea[data-ace-editor]').forEach(function (textarea) {
    const pre = document.createElement('pre')
    const code = document.createElement('code')

    code.classList.add('language-xml')

    textarea.after(pre)
    textarea.hidden = true

    code.textContent = formatXml(textarea.value.trim(), "  ")
    pre.appendChild(code)
  })

  hljs.highlightAll()
}

function initform () {
  updateAce()

  $('button#selectall').click(function () {
    $('input[type=checkbox].source').prop('checked', true)
    return false
  })

  $('button#clearall').click(function () {
    $('input[type=checkbox].source').prop('checked', false)
    return false
  })

  $('button#selectallrules').click(function () {
    $('input[type=checkbox].rule').prop('checked', true)
    return false
  })

  $('button#clearallrules').click(function () {
    $('input[type=checkbox].rule').prop('checked', false)
    return false
  })
}
function formatXml(xml, tab) { // tab = optional indent value, default is tab (\t)
  var formatted = '', indent= '';
  tab = tab || '\t';
  xml.split(/>\s*</).forEach(function(node) {
    if (node.match( /^\/\w/ )) indent = indent.substring(tab.length); // decrease indent by one 'tab'
    formatted += indent + '<' + node + '>\r\n';
    if (node.match( /^<?\w[^>]*[^\/]$/ )) indent += tab;              // increase indent
  });
  return formatted.substring(1, formatted.length-3);
}