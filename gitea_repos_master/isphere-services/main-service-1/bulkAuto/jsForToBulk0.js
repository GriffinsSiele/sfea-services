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

const sites = ['https://i-sphere.ru/2.00/', 'https://i-sphere.ru/2.00/', 'https://my.infohub24.ru/'];
var inWork = [];

async function getData(theLink, path, theFile, respFile){
	inWork.push(theFile);
	try{
//		console.log(theFile);
		const stream = await createReadStream(path+theFile);
//		console.log(path+theFile);
//		console.log(stream);
		const response = await fetch(theLink, {method: 'POST', agent: httpsAgent, body: stream});
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

function tick(siteNum) {
	const limit = parseInt(fs.readFileSync('/opt/forReq/' + siteNum + '/limit.txt'));
	if (inWork.length > 0) {
		console.log('limit is ' + limit);
		console.log(inWork);
	}
	fs.readdir('/opt/forReq/' + siteNum, function(err, files) {
		if(files.length !== undefined && files.length > 0 ){
			files.forEach(function(file, index) {
				if(inWork.length >= limit){
//					console.log('pause');
					return false;
				}else if(inWork.indexOf(file) >= 0 ){
					console.log('in work');
				}else if(file.includes('.qwe')){
					respDir = '/opt/bulk/' + file.replace(/(___|\.qwe)/g,"/");
					respDir = respDir.replace(/\-row[0-9]+/,"");
//					respDir = respDir.replace(/fns_inn/,"fns");
					if (!fs.existsSync(respDir)) {
					    fs.mkdirSync(respDir);
					}
					respFile = respDir + 'fResult.txt';
					if (fs.existsSync('/opt/forReq/' + siteNum + '/' + file) && inWork.indexOf(file) < 0) {
//						inWork.push(file);
//						respFile = '/opt/bulk/' + file.replace(/(___|\.qwe)/g,"/") + 'fResult.txt';
//						console.log(file);
//						console.log(respFile);
						getData(sites[siteNum], '/opt/forReq/' + siteNum + '/', file, respFile);
					}
				}
			});
		}
	});

//	console.log('here');
	setTimeout(tick, 1000, siteNum);
};

tick(0);

//console.log('oplya');