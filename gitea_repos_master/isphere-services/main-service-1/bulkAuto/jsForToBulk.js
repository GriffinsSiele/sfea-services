const fs = require('fs');
const {createReadStream} = require('fs');
const {createWriteStream} = require('fs');
const {pipeline} = require('stream');
const {promisify} = require('util');
const streamPipeline = promisify(pipeline);
const fetch = require('node-fetch');
const https = require('https');
const httpsAgent = new https.Agent({
	rejectUnauthorized: false,
});

const workDir = '/opt/forReq/';
const resDir = '/opt/bulk/';
var queue = {};
var sourceCount = [];

async function getData(id, theFile, respFile){
	const path = workDir + id + '/';
	queue[id].inWork.push(theFile);
	try{
//		console.log(theFile);
		const stream = await createReadStream(path+theFile);
//		console.log(path+theFile);
//		console.log(stream);
		const response = await fetch(queue[id].url, {method: 'POST', agent: httpsAgent, body: stream});
//		console.log(response.body);
		await streamPipeline(response.body, createWriteStream(respFile));
		if (fs.existsSync(path + theFile))
			fs.unlinkSync(path + theFile);
		try{
			queue[id].inWork.splice(queue[id].inWork.indexOf(theFile),1);
		}catch(e){
			console.log(e);
		}
	}catch(e){
		console.log(e);
		try{
			queue[id].inWork.splice(queue[id].inWork.indexOf(theFile),1);
		}catch(e){
			console.log(e);
		}
	}
}

function tickDir(id) {
	if (typeof queue[id] == 'undefined') {
		queue[id] = {};
		queue[id].url = fs.readFileSync(workDir + id + '/url.txt');
		queue[id].files = [];
		queue[id].inWork = [];
		console.log('new queue ' + id + ' for ' + queue[id].url);
	}
	if (fs.existsSync(workDir + id + '/reload.txt')) {
		fs.unlinkSync(workDir + id + '/reload.txt');
		queue[id].files = [];
	}
	queue[id].limit = parseInt(fs.readFileSync(workDir + id + '/limit.txt'));
	if (queue[id].files.length == 0) {
		queue[id].files = fs.readdirSync(workDir + id).filter(function (file) {
			return file.includes('.qwe');
		});
		if (queue[id].files.length > 0)
			console.log('queue ' + id + ': found ' + queue[id].files.length + ' queries');
	}
	if (queue[id].inWork.length > 0)
		console.log('queue ' + id + ': processing ' + queue[id].inWork.length + ' of ' + queue[id].files.length + ' limit ' + queue[id].limit);

	queue[id].files.forEach(function(file, index) {
		if(index >= queue[id].limit || queue[id].inWork.length >= queue[id].limit){
//			console.log('nothing to do');
			return false;
		}else if(queue[id].inWork.indexOf(file) >= 0 ){
//			console.log('already in work');
		}else if(!fs.existsSync(workDir + id + '/' + file)){
			queue[id].files = [];
			console.log('reloading queue ' + id);
			return false;
		}else if(file.includes('.qwe')){
			respDir = resDir + file.replace(/(___|\.qwe)/g,"/");
			respDir = respDir.replace(/\-row[0-9]+/,"");
//			respDir = respDir.replace(/fns_inn/,"fns");
			if (!fs.existsSync(respDir)) {
			    fs.mkdirSync(respDir);
			}
			respFile = respDir + 'fResult.txt';
			if (fs.existsSync(workDir + id + '/' + file) && !fs.existsSync(workDir + id + '/' + respFile) && queue[id].inWork.indexOf(file) < 0) {
				console.log(file);
//				console.log(respFile);
				getData(id, file, respFile);
			}
			try{
				queue[id].files.splice(queue[id].files.indexOf(file),1);
			}catch(e){
				console.log(e);
			}
		}
	});

//	console.log('here');
//	setTimeout(tick, 1000, id);
};

function tickAll() {
	const ids = fs.readdirSync(workDir).filter(function (file) {
		return fs.statSync(workDir + file).isDirectory() && fs.existsSync(workDir + file + '/url.txt') && fs.existsSync(workDir + file + '/limit.txt');
	});
	ids.forEach(function(id){
		tickDir(id);
	});
	for (var id in queue) {
		if(!ids.includes(id) && queue[id].inWork.length==0) {
			delete queue[id];
			console.log('deleted empty queue ' + id);
		}
	};
	setTimeout(tickAll, 1000);
//	console.log(ids);
}

tickAll();
//tick(1);
//tick(2);

//console.log('oplya');