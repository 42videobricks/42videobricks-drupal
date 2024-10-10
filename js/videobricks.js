(function ($) {
  jQuery(document).ready( function() {
    $.parts = {}
    $.aborted = false;
    $.progressCache = []
    $.uploadedParts = []
    $.uploadedSize = 0
    $.nbParts = 0;
    $.file = null;
    $.activeConnections = {};
    $.threadsQuantity = 5;
    $.chunkSize = 0;
    $.fileId = null
    $.fileKey = null
    $.onProgressFn = () => {
    }
    $.onErrorFn = () => {
    }


    const dropArea = document.querySelector('#dragAndDropContainer');
      const input = document.querySelector('#videobricksFileInput');

      input.addEventListener('change', videoUploadAction);
      dropArea.addEventListener('drop', videoUploadAction);

      function videoUploadAction() {
        document.getElementById("video-drop-container-gret").style.display = "block";
        document.getElementById("videobricks-upload-progress-bar").style.display = "flex";

        document.getElementById("dragAndDropContainer").classList.add("currentUpload");
        document.getElementById("videobricksFileInput").disabled = true;
        var fd = new FormData();
        fd.append('name', input.files[0].name);
        fd.append('size', input.files[0].size);
        const url = Drupal.url('admin/config/videobricks/init');

        $.ajax({
          url: url,
          method : 'POST',
          contentType: false,
          processData: false,
          data: fd,
          success : function( data ) {
            let response = data.response;
            $.videoId = data.videoId;
            $.parts = response.parts;
            $.nbParts = response.parts.length;
            $.chunkSize = response.chunkSize;
            $.fileKey = response.fileKey;
            $.fileId = response.fileId;
            $.file = input.files[0];
            sendNext(response);
          },
          error: function (response) {
            document.getElementById("videobricks-video-information").innerHTML = '<p class="videobricks-error-message">' + response.responseJSON + '<p>';
            document.getElementById("videobricksFileInput").disabled = false;
          }
        });
      }
    function sendNext(retry = 0) {
      const activeConnections = Object.keys($.activeConnections).length
      if (activeConnections >= $.threadsQuantity) {
        return
      }

      if (!$.parts.length) {
        if (!activeConnections) {
         complete()
        }
        return
      }

      const part = $.parts.pop();//récupération de la part disponible
      if ($.file && part) {
        const sentSize = (part.PartNumber - 1) * $.chunkSize
        const chunk = $.file.slice(sentSize, sentSize + $.chunkSize)

        const sendChunkStarted = () => {
          sendNext()
        }

        sendChunk(chunk, part, sendChunkStarted)
            .then(() => {
              //throw new Error('Je casse tout');
              sendNext()
            })
            .catch((error) => {
              if (retry <= 6) {
                retry++
                const wait = (ms) => new Promise((res) => setTimeout(res, ms));
                wait(2 * retry * 100).then(() => {
                  $.parts.push(part)
                  sendNext(retry)
                })
              } else {
                complete(error)
              }
            })

      }
    }

   function sendChunk(chunk, part, sendChunkStarted) {
      return new Promise((resolve, reject) => {
        upload(chunk, part, sendChunkStarted)
            .then((status) => {
              if (status !== 200) {
                reject(new Error("Failed chunk upload"))
                return
              }
              resolve()
            })
            .catch((error) => {
              reject(error)
            })
      })
    }

   function upload(file, part, sendChunkStarted) {
      // uploading each part with its pre-signed URL
      return new Promise((resolve, reject) => {
        const throwXHRError = (error, part, abortFx) => {
          delete $.activeConnections[part.PartNumber - 1]
          reject(error)
          window.removeEventListener('offline', abortFx)
        }
        if ($.fileId && $.fileKey) {
          if (!window.navigator.onLine)
            reject(new Error("System is offline"))

          const xhr = ($.activeConnections[part.PartNumber - 1] = new XMLHttpRequest())
          xhr.timeout = $.timeout
          sendChunkStarted()

          const progressListener = handleProgress.bind($, part.PartNumber - 1)

          xhr.upload.addEventListener("progress", progressListener)

          xhr.open("PUT", part.signedUrl)
          const abortXHR = () => xhr.abort()
          xhr.onreadystatechange = () => {
            if (xhr.readyState === 4 && xhr.status === 200) {
              const ETag = xhr.getResponseHeader("ETag")

              if (ETag) {
                const uploadedPart = {
                  PartNumber: part.PartNumber,
                  ETag: ETag.replaceAll('"', ""),
                }

                $.uploadedParts.push(uploadedPart)

                resolve(xhr.status)
                delete $.activeConnections[part.PartNumber - 1]
                window.removeEventListener('offline', abortXHR)
              }
            }
          }

          xhr.onerror = (error) => {
            throwXHRError(error, part, abortXHR)
          }
          xhr.ontimeout = (error) => {
            throwXHRError(error, part, abortXHR)
          }
          xhr.onabort = () => {
            throwXHRError(new Error("Upload canceled by user or system"), part)
          }
          window.addEventListener('offline', abortXHR);
          xhr.send(file)
        }
      })
    }
   function complete(error) {
      try {
        sendCompleteRequest()
      } catch (error) {
        $.onErrorFn(error)
      }
    }
    function sendCompleteRequest() {
      if ($.fileId && $.fileKey) {
        const videoFinalizationMultiPartInput = {
          fileId: $.fileId,
          fileKey: $.fileKey,
          parts: $.uploadedParts,
          videoId: $.videoId,
        };
        const url = Drupal.url('admin/config/videobricks/finalize');

        $.ajax({
          url: url,
          method : 'POST',
          dataType : "json",
          data: videoFinalizationMultiPartInput,
          success : function( response ) {
            $.parts = {}
            $.aborted = false;
            $.progressCache = []
            $.uploadedParts = []
            $.uploadedSize = 0
            $.nbParts = 0;
            $.file = null;
            $.activeConnections = {};
            $.threadsQuantity = 5;
            $.chunkSize = 0;
            $.fileId = null
            $.fileKey = null
            $.onProgressFn = () => {
            }
            $.onErrorFn = () => {
            }
            document.getElementById("videobricks-video-information").innerHTML = "Watch the video <a href='/admin/config/videobricks/library'>here</a>";
            document.getElementById("videobricksFileInput").disabled = false;
          }
        });
      }
    }
    function handleProgress(part, event) {
      if ($.file) {
        if (event.type === "progress" || event.type === "error" || event.type === "abort") {
          $.progressCache[part] = event.loaded
        }

        if (event.type === "uploaded") {
          $.uploadedSize += $.progressCache[part] || 0
          delete $.progressCache[part]
        }

        const inProgress = Object.keys($.progressCache)
            .map(Number)
            .reduce((memo, id) => (memo += $.progressCache[id]), 0)
        const sent = Math.min($.uploadedSize + inProgress, $.file.size)

        const total = $.file.size

        const percentage = Math.round((sent / total) * 100)

        this.onProgressFn({
          sent: sent,
          total: total,
          percentage: percentage,
        })
        document.getElementById("progress-tracker").value = percentage;
        document.getElementById("videobricks-upload-information").innerHTML = Math.ceil(percentage)+"%";
      }
    }
  });
})(jQuery);
