// Hash Worker - 后台线程计算 SparkMD5
// 使用 Blob URL 方式加载，不依赖外部 CDN
self.importScripts('https://s4.zstatic.net/ajax/libs/spark-md5/3.0.2/spark-md5.min.js');

self.onmessage = function(e) {
    var file = e.data.file;
    var chunkSize = 2 * 1024 * 1024; // 2MB for hash
    var spark = new SparkMD5.ArrayBuffer();
    var reader = new FileReaderSync();
    var offset = 0;
    var chunkIndex = 0;
    var total = Math.ceil(file.size / chunkSize);
    
    while(offset < file.size){
        var end = Math.min(offset + chunkSize, file.size);
        var slice = file.slice(offset, end);
        var buffer = reader.readAsArrayBuffer(slice);
        spark.append(buffer);
        offset = end;
        chunkIndex++;
        // 每个分片回报进度
        self.postMessage({ type: 'progress', current: chunkIndex, total: total });
    }
    
    var hash = spark.end();
    self.postMessage({ type: 'done', hash: hash });
};
