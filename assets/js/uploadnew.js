new Vue({
    el: '#app',
    data: {
        uploadTitle: '选择文件/Ctrl+V粘贴/拖拽到此处上传',
        background: '#fff',
        showtype: 0,
        ready: false,
        showPwdTip: false,
        isBlock: false,
        alert: { type: 'success', msg: '' },
        toast: { show: false, msg: '', type: '', hide: true },
        beginTime: 0,
        loaded_size: 0,
        progress: 0,
        progress_tip: '',
        filename: '',
        uploadspeed: '',
        shapeIndex: 0,
        isReading: true,
        statusText: '准备上传...',
        currentHash: '',
        input: {
            csrf_token:'', show: true, ispwd: false, pwd: '', hash: '', name: '', size: 0
        },
        // Batch state
        fileQueue: [],
        fileResults: [],
        fileErrors: [],
        batchTotal: 0,
        batchDone: 0,
        uploading: false,
        logs: []
    },
    mounted() {
        // 注册到全局，供 showToast 等同步调用 Vue 实例
        window._zkVue = this;
        $(".colorful_loading_frame").hide();
        this.input.csrf_token = $("#csrf_token").val();
        // 标记 Vue 已就绪，显示进度条区域
        this.ready = true;

        // 防止上传中误关闭页面
        var that = this;
        window.addEventListener('beforeunload', function(e) {
            if(that.uploading){
                e.preventDefault();
                e.returnValue = '正在上传文件，关闭后将中断上传，确认要关闭？';
                // 用 sendBeacon 清理临时文件（页面关闭后异步执行）
                var blob = new Blob([JSON.stringify({
                    csrf_token: that.input.csrf_token,
                    hash: that.currentHash || ''
                })], {type: 'application/json'});
                navigator.sendBeacon('ajax.php?act=cleanup_temp', blob);
                return e.returnValue;
            }
        });

        // Keep-alive: 防止浏览器标题静默，每3秒刷一次
        this._keepAlive = setInterval(function(){
            if(that.uploading){
                document.title = '上传中... ' + that.batchDone + '/' + that.batchTotal + ' (' + that.filename + ')';
            } else {
                document.title = that._origTitle || document.title;
            }
        }, 1000);
        this._origTitle = document.title;

        // 图标形状变化动画定时器
        this._shapeTimer = setInterval(function(){
            that.shapeIndex = (that.shapeIndex + 1) % 4;
        }, 800);

        var fileInput = $("#fileInput");
        var elemetnNode = "";
        fileInput.on("dragenter", function(e){
            elemetnNode = e.originalEvent.target;
            that.uploadTitle = '释放即可上传';
            that.background = '#ccc';
        });
        fileInput.on("dragleave", function(e){
            if(elemetnNode === e.originalEvent.target){
                that.uploadTitle = '选择文件/Ctrl+V粘贴/拖拽到此处上传';
                that.background = '#fff';
            }
        });
        fileInput.on('dragover', false).on("drop", function(e){
            that.uploadTitle = '选择文件/Ctrl+V粘贴/拖拽到此处上传';
            that.background = '#fff';
            if(e.originalEvent.dataTransfer.files.length > 0){
                that.selectFile({target: {files: e.originalEvent.dataTransfer.files}});
            }
            return false;
        });

        document.addEventListener('paste', function(e) {
            var items = ((e.clipboardData || window.clipboardData).items) || [];
            var file = null;
            if (items && items.length) {
                for (var i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('text/') === -1) {
                        file = items[i].getAsFile();
                        break;
                    }
                }
            }
            if (!file) return;
            that.selectFile({target: {files: [file]}});
        });
    },
    methods: {
        // Bug fix: drag event handlers moved from Vue.prototype to methods
        onDragOver: function() {
            var box = document.getElementById('uploadBox');
            if (box) box.classList.add('drag-over');
        },
        onDragLeave: function() {
            var box = document.getElementById('uploadBox');
            if (box) box.classList.remove('drag-over');
        },
        onDrop: function(e) {
            var box = document.getElementById('uploadBox');
            if (box) box.classList.remove('drag-over');
            if (e.dataTransfer && e.dataTransfer.files.length > 0) {
                var fileInput = document.getElementById('file');
                fileInput.files = e.dataTransfer.files;
                var event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        },
        setStatusText: function(text){
            this.statusText = text;
            // 同时用 jQuery 直接更新 DOM（双保险）
            $(".loading-info .status").text(text);
        },
        updateStatusText: function(){
            var text = this.isReading
                ? ('读取文件 ' + this.progress + '%')
                : (this.progress_tip || this.progress + '% 上传中...');
            this.setStatusText(text);
        },
        showToastMsg: function(msg, type, hideDelay){
            type = type || 'info';
            // 兼容全局 showToast（DOM 版）
            if (typeof showToast === 'function') showToast(msg, type, hideDelay || 5000);
            // 同步更新 Vue 内部 toast 数据（模板使用）
            if (this.toast) {
                this.toast.show = true;
                this.toast.msg = msg;
                this.toast.type = type;
                this.toast.hide = false;
                var that = this;
                setTimeout(function(){ if(that.toast) that.toast.hide = true; }, 4500);
                setTimeout(function(){ if(that.toast) that.toast.show = false; }, 5000);
            }
            // 也写入日志
            if (typeof this.addLog === 'function') this.addLog(msg, type);
        },
        addLog: function(msg, type){
            type = type || 'info';
            var now = new Date().toLocaleTimeString();
            this.logs.unshift({time: now, msg: msg, type: type});
            if(this.logs.length > 50) this.logs.pop();
            console.log('[' + type.toUpperCase() + '] ' + msg);
        },
        clickUpload: function(){
            if(this.isBlock) return;
            $("#file").trigger("click");
        },
        selectFile: async function(e){
            var files = e.target.files;
            var total = files.length;
            if(total == 0) return;

            if(typeof forbid!=='undefined' && forbid){
                zkAlert({ icon: 'warning', title: '请先登录', subtitle: '登录后才能上传文件', btnText: '去登录', btnClass: 'btn-primary', onConfirm: function(){ window.location.href='./login.php'; } });
                return;
            }
            if(this.input.ispwd && !this.input.pwd){
                this.alert = { type: 'warning', msg: '请输入文件提取密码后再上传' };
                this.showtype = 2;
                return;
            }

            var isBatch = total > 1;
            var concurrency = isBatch ? (typeof upload_concurrent !== 'undefined' ? upload_concurrent : 5) : 1;
            concurrency = Math.min(concurrency, 50, total);

            // Init state
            this.fileQueue = [];
            this.fileResults = [];
            this.fileErrors = [];
            this.batchTotal = total;
            this.batchDone = 0;
            this.beginTime = new Date().getTime();

            for(var i = 0; i < total; i++){
                this.fileQueue.push({
                    index: i,
                    file: files[i],
                    name: files[i].name,
                    size: files[i].size,
                    status: 'waiting'
                });
            }

            this.uploading = true;
            if(isBatch){
                this.showtype = 1;
                $("#progressBarFrame").show();
                this.isReading = true;
                this.filename = '批量上传准备中...';
                this.progress_tip = '上传中 0/' + total;
                this.updateStatusText();
                this.showToastMsg('开始上传 ' + total + ' 个文件', 'info');
            }

            // Process sequentially or concurrently
            if(isBatch){
                // Run concurrent batches
                for(var start = 0; start < this.fileQueue.length; start += concurrency){
                    var batch = this.fileQueue.slice(start, start + concurrency);
                    var promises = [];
                    for(var j = 0; j < batch.length; j++){
                        promises.push(this.processOneFile(batch[j], isBatch));
                    }
                    await Promise.all(promises);
                    // 批次间加延迟，防止瞬时请求过多
                    if(start + concurrency < this.fileQueue.length){
                        await new Promise(function(r){ setTimeout(r, 300); });
                    }
                }
            } else {
                await this.processOneFile(this.fileQueue[0], false);
            }

            this.uploading = false;
            if(isBatch){
                this.showBatchSummary();
            }

            this.uploadTitle = '选择文件 / Ctrl+V粘贴 / 拖拽文件到此处上传';
            this.showtype = 0;
            e.target.value = '';
        },
        processOneFile: async function(item, isBatch){
            var self = this;
            var Vue = self.$root.constructor;

            // Mark uploading
            Vue.set(item, 'status', 'uploading');
            if(isBatch){
                self.filename = item.name;
                self.progress_tip = '上传中 ' + self.batchDone + '/' + self.batchTotal;
                self.updateStatusText();
            } else {
                self.uploadTitle = '上传中: ' + item.name;
                self.showtype = 1;
                $("#progressBarFrame").show();
            }
            self.addLog('上传开始', item.name, 'size:', item.size);

            // Validate size
            if(upload_max_filesize != '' && parseInt(upload_max_filesize) > 0){
                if(item.size > parseInt(upload_max_filesize) * 1024 * 1024){
                    Vue.set(item, 'status', 'error');
                    self.fileErrors.push({name: item.name, reason: '超过' + upload_max_filesize + 'MB'});
                    self.batchDone++;
                    if(isBatch) self.showToastMsg(item.name + ' 文件过大', 'error');
                    self.addLog('文件过大', item.name);
                    return;
                }
            }

            try {
                var itemHash = '';
                self.progress = 0;
                self.loaded_size = 0;

                // Hash
                self.isReading = true;
                self.progress = 0;
                // ===== 显示进度条，开始读取 =====
                self.showtype = 1;
                self.isReading = true;
                self.progress = 0;
                self.filename = item.name;
                // 强制 jQuery 显示进度条
                $("#progressBarFrame").show();
                
                // ===== Web Worker 后台算 Hash，失败则回退同步 =====
                self.addLog('计算Hash', item.name);
                var hashResult = '';
                var hashStarted = false;
                
                try {
                    var hashWorker = new Worker('./assets/js/hash-worker.js');
                    hashWorker.postMessage({ file: item.file });
                    hashWorker.onmessage = function(e){
                        hashStarted = true;
                        if(e.data.type === 'progress'){
                            self.filename = '读取 ' + item.name + ' (' + Math.round(e.data.current / e.data.total * 100) + '%)';
                        } else if(e.data.type === 'done'){
                            hashResult = e.data.hash;
                            self.currentHash = hashResult;
                            self.addLog('Hash完成', item.name, hashResult);
                            hashWorker.terminate();
                            startUpload();
                        }
                    };
                    hashWorker.onerror = function(e){
                        console.log('[Hash Worker 失败, 回退同步]', e);
                        hashWorker.terminate();
                        syncHash();
                    };
                    // 5秒超时保护：Worker 卡住就回退
                    setTimeout(function(){
                        if(!hashStarted && !hashResult){
                            console.log('[Hash Worker 超时, 回退同步]');
                            hashWorker.terminate();
                            syncHash();
                        }
                    }, 5000);
                } catch(e){
                    syncHash();
                }

                // 同步 Hash 回退
                async function syncHash(){
                    self.addLog('Worker不可用，同步计算Hash');
                    hashResult = await self.getFileHash(item.file);
                    self.currentHash = hashResult;
                    self.addLog('Hash完成', item.name, hashResult);
                    startUpload();
                }

                // Hash 完成后启动上传
                async function startUpload(){
                    self.isReading = false;
                    self.progress = 0;
                    self.filename = item.name;
                    // 强制触发 Vue 更新 + jQuery 显示
                    self.$nextTick(function(){
                        $(".progress-bar-frame").show();
                    });

                    self.addLog('预上传', item.name);
                    var result = await self.preUpload(item.name, hashResult, item.size);
                    self.addLog('预上传结果', item.name, result);
                    if(result.code == 1){
                        Vue.set(item, 'status', 'done');
                        self.fileResults.push({name: item.name, url: "file.php?hash=" + hashResult});
                        self.batchDone++;
                        self.addLog('文件已存在', item.name);
                        return;
                    }

                    var chunkSize = result.chunksize || 2 * 1024 * 1024;
                    var totalChunks = result.chunks || 1;
                    var blobSlice = File.prototype.mozSlice || File.prototype.webkitSlice || File.prototype.slice;

                    if(totalChunks == 1){
                        self.progress_tip = '上传中 (1/1)';
                        await self.uploadPart(item.file, 1, hashResult);
                        self.progress = 100;
                    } else {
                        var allChunks = [];
                        var offset = 0;
                        while(offset < item.size){
                            allChunks.push(blobSlice.call(item.file, offset, Math.min(offset + chunkSize, item.size)));
                            offset += chunkSize;
                        }
                        var concurrency = Math.min(typeof upload_concurrent !== 'undefined' ? upload_concurrent : 2, 3, totalChunks);
                        self.addLog('并发上传', '分片数:' + totalChunks, '并发度:' + concurrency);
                        for(var bs = 1; bs <= totalChunks; bs += concurrency){
                            var promises = [];
                            var be = Math.min(bs + concurrency, totalChunks + 1);
                            for(var c = bs; c < be; c++){
                                (function(idx){
                                    promises.push(self.uploadPart(allChunks[idx-1], idx, hashResult).then(function(){
                                        self.progress = Math.round(idx * chunkSize / item.size * 100);
                                        self.progress_tip = '上传中 (' + idx + '/' + totalChunks + ')';
                                        self.updateStatusText();
                                    }));
                                })(c);
                            }
                            await Promise.all(promises);
                            if(bs + concurrency <= totalChunks) await new Promise(function(r){ setTimeout(r, 500); });
                        }
                        allChunks = null;
                    }

                    // 合并
                    self.progress_tip = '合并中...';
                    self.updateStatusText();
                    self.addLog('提交合并 ' + item.name);
                    var completeResult = await self.completeUpload(hashResult);
                    self.addLog('完成返回 ' + item.name + ': ' + JSON.stringify(completeResult));
                    var jumpurl = "file.php?hash=" + hashResult;
                    if(self.input.ispwd && self.input.pwd) jumpurl += '&pwd=' + self.input.pwd;
                    Vue.set(item, 'status', 'done');
                    self.fileResults.push({name: item.name, url: jumpurl});
                    self.batchDone++;
                    self.showtype = 0; // 隐藏进度条，刷新界面
                    $("#progressBarFrame").hide();
                    if(!isBatch) self.uploadSuccess(hashResult);
                    self.addLog('✓ 成功', item.name);
                }

                return; // 跳过下面的旧流程

                // Pre upload
                self.addLog('预上传', item.name);
                var result = await self.preUpload(item.name, hash, item.size);
                self.addLog('预上传结果', item.name, result);
                if(result.code == 1){
                    var jumpurl = "file.php?hash=" + hash;
                    if(self.input.ispwd && self.input.pwd) jumpurl += '&pwd=' + self.input.pwd;
                    Vue.set(item, 'status', 'done');
                    self.fileResults.push({name: item.name, url: jumpurl});
                    self.batchDone++;
                    if(isBatch) self.showToastMsg(item.name + ' 已存在', 'info');
                    self.addLog('文件已存在', item.name);
                    return;
                }

                // Upload content
                self.progress_tip = '上传中...';
                self.updateStatusText();
                if(result.third){
                    self.addLog('第三方上传', item.name);
                    await self.uploadThird(result.url, result.post, item.file);
                    await self.completeUpload(hash);
                } else {
                    var chunkSize = result.chunksize || 2 * 1024 * 1024;
                    var chunks = result.chunks || 1;
                    self.addLog('分片上传', item.name, '分片数:', chunks, '大小:', chunkSize);
                    if(chunks == 1){
                        self.progress_tip = '上传中 ' + item.name + ' (1/1)';
                        self.progress = 50;
                        self.updateStatusText();
                        await self.uploadPart(item.file, 1, hash);
                        self.progress = 100;
                        self.updateStatusText();
                    } else {
                        var blobSlice = File.prototype.mozSlice || File.prototype.webkitSlice || File.prototype.slice;
                        // 分片并发上传，降低并发度保证稳定性
                        var chunkConcurrency = Math.min(typeof upload_concurrent !== 'undefined' ? upload_concurrent : 3, 5, chunks);
                        self.addLog('并发上传', '分片数:' + chunks, '并发度:' + chunkConcurrency);
                        for(var batchStart = 1; batchStart <= chunks; batchStart += chunkConcurrency){
                            var promises = [];
                            var batchEnd = Math.min(batchStart + chunkConcurrency, chunks + 1);
                            for(var c = batchStart; c < batchEnd; c++){
                                (function(chunkIdx){
                                    var start = (chunkIdx - 1) * chunkSize;
                                    var end = Math.min(start + chunkSize, item.size);
                                    var blob = blobSlice.call(item.file, start, end);
                                    promises.push(
                                        self.uploadPart(blob, chunkIdx, hash).then(function(){
                                            self.loaded_size = Math.min(chunkIdx * chunkSize, item.size);
                                            self.progress = Math.round(self.loaded_size / item.size * 100);
                                            self.progress_tip = '上传中 ' + item.name + ' (' + chunkIdx + '/' + chunks + ')';
                                            self.updateStatusText();
                                        })
                                    );
                                })(c);
                            }
                            await Promise.all(promises);
                            // 批次间延迟，等待服务端写入完成
                            if(batchStart + chunkConcurrency <= chunks){
                                await new Promise(function(r){ setTimeout(r, 500); });
                            }
                        }
                    }
                }

                // Complete
                self.addLog('上传完成，提交合并 ' + item.name);
                var completeResult;
                try {
                    completeResult = await self.completeUpload(hash);
                    self.addLog('完成返回 ' + item.name + ': ' + JSON.stringify(completeResult));
                } catch(cErr) {
                    var errMsg = typeof cErr === 'string' ? cErr : JSON.stringify(cErr);
                    self.addLog('completeUpload异常 ' + item.name + ': ' + errMsg, 'error');
                    throw cErr;
                }

                // Success
                var jumpurl = "file.php?hash=" + hash;
                if(self.input.ispwd && self.input.pwd) jumpurl += '&pwd=' + self.input.pwd;
                Vue.set(item, 'status', 'done');
                self.fileResults.push({name: item.name, url: jumpurl});
                self.batchDone++;
                self.showtype = 0;
                $("#progressBarFrame").hide();
                if(!isBatch){
                    self.uploadSuccess(hash);
                }
                self.addLog('✓ 成功', item.name);

            } catch(err) {
                Vue.set(item, 'status', 'error');
                var reason = typeof err === 'string' ? err : '上传失败';
                self.fileErrors.push({name: item.name, reason: reason});
                self.batchDone++;
                if(isBatch) self.showToastMsg(item.name + ' 失败: ' + reason, 'error');
                self.addLog('✗ 失败', item.name, reason);
                // 清理临时文件
                $.ajax({
                    type: 'POST', url: 'ajax.php?act=cleanup_temp',
                    data: {hash: hash || '', csrf_token: self.input.csrf_token},
                    dataType: 'json', timeout: 5000
                });
            }
        },
        showBatchSummary: function(){
            var self = this;
            var s = this.fileResults.length;
            var f = this.fileErrors.length;
            var time = ((new Date().getTime() - this.beginTime) / 1000).toFixed(1);

            if(f > 0){
                self.showToastMsg('上传完成：成功 ' + s + ' 个，失败 ' + f + ' 个，用时 ' + time + ' 秒', 'warning', 8000);
            } else {
                self.showToastMsg('✓ 全部 ' + s + ' 个文件上传成功，用时 ' + time + ' 秒', 'success', 6000);
            }
        },
        preUpload: function(name, hash, size){
            var that = this;
            return new Promise(function(resolve, reject){
                $.ajax({
                    type: 'POST', url: 'ajax.php?act=pre_upload',
                    data: { csrf_token: that.input.csrf_token, name: name, hash: hash, size: size, show: that.input.show?'1':'0', ispwd: that.input.ispwd?'1':'0', pwd: that.input.pwd },
                    dataType: 'json',
                    timeout: 120000,
                    success: function(data){
                        console.log('[preUpload response]', data);
                        (data.code == 0 || data.code == 1) ? resolve(data) : reject(data.msg);
                    },
                    error: function(xhr, status, err){
                        var resp = xhr.responseText || '';
                        console.log('[preUpload error]', status, err, resp);
                        reject('预上传失败: ' + (resp || err || status));
                    }
                });
            });
        },
        completeUpload: function(hash){
            var that = this;
            return new Promise(function(resolve, reject){
                $.ajax({
                    type: 'POST', url: 'ajax.php?act=complete_upload',
                    data: {hash: hash, csrf_token: that.input.csrf_token}, dataType: 'json',
                    timeout: 60000,
                    success: function(data){ (data.code == 0 || data.code == 1) ? resolve(data) : reject(data.msg); },
                    error: function(){ reject('完成请求失败'); }
                });
            });
        },
        uploadPart: function(file, chunk, hash){
            var that = this;
            var maxRetries = 3;
            var attempt = 0;
            return new Promise(function doUpload(resolve, reject){
                attempt++;
                var data = new FormData();
                data.append('file', file);
                data.append('hash', hash);
                data.append('chunk', chunk);
                data.append('csrf_token', that.input.csrf_token);
                $.ajax({
                    type: "POST", url: "ajax.php?act=upload_part",
                    data: data, processData: false, contentType: false, dataType: 'json',
                    timeout: 120000, // 2分钟超时
                    success: function(data){ (data.code == 0 || data.code == 1) ? resolve(data) : reject(data.msg); },
                    error: function(xhr, status, err){
                        if(attempt < maxRetries && status !== 'abort'){
                            console.log('[retry] chunk '+chunk+' attempt '+attempt+' failed, retrying...');
                            setTimeout(function(){ doUpload(resolve, reject); }, 1000 * attempt);
                        } else {
                            reject('上传失败: ' + (status || err));
                        }
                    }
                });
            });
        },
        uploadThird: function(url, postdata, file){
            return new Promise(function(resolve, reject){
                var data = new FormData();
                for(var key in postdata) data.append(key, postdata[key]);
                data.append('file', file);
                $.ajax({
                    type: "POST", url: url,
                    data: data, processData: false, contentType: false, dataType: 'html',
                    success: function(data){ resolve(data); },
                    error: function(){ reject('上传失败'); }
                });
            });
        },
        getFileHash: function(file){
            var that = this;
            var Vue = that.$root.constructor;
            return new Promise(function(resolve, reject){
                var fileReader = new FileReader(),
                    blobSlice = File.prototype.mozSlice || File.prototype.webkitSlice || File.prototype.slice,
                    chunkSize = 2097152,
                    chunks = Math.ceil(file.size / chunkSize),
                    currentChunk = 0,
                    spark = new SparkMD5();

                fileReader.onload = function(e){
                    spark.appendBinary(e.target.result);
                    currentChunk++;
                    var pct = Math.round(currentChunk/chunks*100);
                    Vue.set(that, 'progress', pct);
                    Vue.set(that, 'filename', '读取 ' + file.name + ' (' + pct + '%)');
                    that.updateStatusText();
                    if(currentChunk < chunks) loadNext();
                    else resolve(spark.end());
                };
                fileReader.onerror = function(){ reject('文件读取失败'); };
                function loadNext(){
                    var start = currentChunk * chunkSize;
                    var end = Math.min(start + chunkSize, file.size);
                    fileReader.readAsBinaryString(blobSlice.call(file, start, end));
                }
                loadNext();
            });
        },
        uploadSuccess: function(hash){
            var lastTime = (new Date().getTime() - this.beginTime) / 1000;
            this.showToastMsg('✓ 上传成功！用时 ' + lastTime.toFixed(2) + ' 秒', 'success', 5000);
        }
    }
});
