const fs = require('fs');
const {createReadStream} = require('fs');
const {createWriteStream} = require('fs');
const {pipeline} = require('stream');
const {promisify} = require('util');

const fetch = require('node-fetch');

const streamPipeline = promisify(pipeline);



var inWork = [];

async function getData(theLink, path, theFile, respFile){
	inWork.push(theFile);
	try{
//		console.log(theFile);
		const stream = await createReadStream(path+theFile);
//		console.log(path+theFile);
//		console.log(stream);
		const response = await fetch(theLink, {method: 'POST', body: stream});
//		console.log(response.body);
		await streamPipeline(response.body, createWriteStream(respFile));
		fs.unlinkSync(path+theFile);

		try{
			inWork.splice(inWork.indexOf(theFile),1);
		}catch(e){
			console.log(e);
		}

	}catch(e){
		console.log(e);
		try{
			inWork.splice(inWork.indexOf(theFile),1);
		}catch(e){
			console.log(e);
		}
	}
}

function tick() {
	var limit = 10; //parseInt(createReadStream('/opt/forReq/limit1.txt'));
	console.log(inWork);
	fs.readdir('/opt/forReq/1', function(err, files) {
		if(files.length !== undefined && files.length > 0 ){
			files.forEach(function(file, index) {
				if(inWork.length >= limit){
//					console.log('pause');
					return false;
				}else if(inWork.indexOf(file) >= 0 ){
					console.log('in work');
				}else if(file.includes('.qwe')){
					respFile = '/opt/bulk/' + file.replace(/(___|\.qwe)/g,"/") + 'fResult.txt';
					respFile = respFile.replace(/_inn/,"");
					if (fs.existsSync('/opt/forReq/1/' + file) && inWork.indexOf(file) < 0) {
//						inWork.push(file);
//						respFile = '/opt/bulk/' + file.replace(/(___|\.qwe)/g,"/") + 'fResult.txt';
//						console.log(file);
//						console.log(respFile);
						getData('https://i-sphere.ru/2.00/', '/opt/forReq/1/', file, respFile);
					}
				}
			});
		}
	});

//	console.log('here');
	setTimeout(tick, 1000);
};

function tak() {
	var limit = 10; //parseInt(createReadStream('/opt/forReq/limit2.txt'));
	console.log('limit is ' + limit);
	console.log(inWork);
	fs.readdir('/opt/forReq/2', function(err, files) {
		console.log(files.length + ' files was found');
		if(files.length !== undefined && files.length > 0 ){
			files.forEach(function(file, index) {
				if(inWork.length >= limit){
					return false;
				}else if(inWork.indexOf(file) >= 0 ){
					console.log(file + ' is processing');
				}else if(file.includes('.qwe')){
					respFile = '/opt/bulk/' + file.replace(/(___|\.qwe)/g,"/") + 'fResult.txt';
					respFile = respFile.replace(/_inn/,"");
					if (fs.existsSync('/opt/forReq/2/' + file) && inWork.indexOf(file) < 0) {
						getData('https://my.infohub24.ru/', '/opt/forReq/2/', file, respFile);
					}
				}
			});
		}
	});
	setTimeout(tak, 1000);
};

tick();
tak();



console.log('oplya');