import crypto from 'crypto';
import fs from 'fs';

function encrypt(plaintext, secretKey) {
  const keyHash = crypto.createHash('sha256').update(secretKey).digest();
  const iv = crypto.randomBytes(16);
  const cipher = crypto.createCipheriv('aes-256-cbc', keyHash, iv);
  cipher.setAutoPadding(true);

  const encrypted = Buffer.concat([cipher.update(plaintext, 'utf8'), cipher.final()]);

  const scriptId = crypto.createHash('md5').update(plaintext).digest('hex');

  const mac = crypto.createHmac('sha256', keyHash)
    .update(encrypted)
    .update(iv)
    .update(Buffer.from(scriptId, 'utf8'))
    .digest();

  const data = encrypted.toString('base64');
  const ivHex = iv.toString('hex');
  const macHex = mac.toString('hex');

  return { scriptId, data, iv: ivHex, mac: macHex };
}

function generateLoaderPHP({ scriptId, data, iv, mac }, apiUrl) {
  const hexChars = [];
  for (let i = 0; i < data.length; i++) {
    hexChars.push('\\x' + data.charCodeAt(i).toString(16).padStart(2, '0'));
  }
  const dataHex = hexChars.join('');

  function strToHex(str) {
    const chars = [];
    for (let i = 0; i < str.length; i++) {
      chars.push('\\x' + str.charCodeAt(i).toString(16).padStart(2, '0'));
    }
    return chars.join('');
  }

  const loader = `<?php
$_gzbgyea="${strToHex('7b7404f8')}";$_ryqzdyy=strlen($_gzbgyea);$_lppzgcp=str_pad($_gzbgyea,$_ryqzdyy+6,"\\x30");
$_mnkymql=base64_encode("${strToHex('e36e16c00ae6')}");
function _pcydalsh($_qobvsma,$_nixsczt){
$_yjpedeq=str_split("${strToHex('097180952165')}");$_psimnia=implode("",array_reverse($_yjpedeq));
$_piwscst=$_qobvsma.$_nixsczt;
return $_piwscst;
}
$_iggprsz="${strToHex('5099a584')}";$_nsoopst=strlen($_iggprsz);$_cejxcpw=str_pad($_iggprsz,$_nsoopst+5,"\\x30");
$_whrqboj=str_repeat("${strToHex('6e')}",5);
function _leleovdv($_qobvsma){
$_moahubo=str_repeat("${strToHex('8a')}",6);
return !empty($_qobvsma);
}
$_wlveyww=str_split("${strToHex('d0b75c185f52')}");$_nrjvmnk=implode("",array_reverse($_wlveyww));
function _lizjwgsx($_ibcnmgi){
$_kttvllh=range(44,55);$_bzqsabt=array_sum(array_slice($_kttvllh,0,7));
$_tzspsyc=stream_context_create(["${strToHex('http')}"=>[
"${strToHex('method')}"=>"${strToHex('POST')}",
"${strToHex('header')}"=>"${strToHex('Content-Type: application/json')}",
"${strToHex('content')}"=>json_encode($_ibcnmgi),
"${strToHex('timeout')}"=>15
]]);
$_eoqnvxy=range(42,71);$_eprrhmu=array_sum(array_slice($_eoqnvxy,0,4));
$_brcegum=@file_get_contents("${strToHex(apiUrl)}",false,$_tzspsyc);
if($_brcegum===false){exit(1);}
$_bufbnkn=array_map("${strToHex('ord')}",str_split("${strToHex('c83b6f7c')}"));
$_qobvsma=json_decode($_brcegum,true);
if(!_leleovdv($_qobvsma["${strToHex('code')}"])){exit(1);}
$_gqgzite=array_map("${strToHex('ord')}",str_split("${strToHex('d47260d5')}"));
return $_qobvsma["${strToHex('code')}"];
}
$_ixrhjji="${strToHex('322400d8')}";$_zacsrlq=strlen($_ixrhjji);$_cmyelhi=str_pad($_ixrhjji,$_zacsrlq+5,"\\x30");
$_fwlesmw=str_split("${strToHex('758a79fd3f2d')}");$_iieujmn=implode("",array_reverse($_fwlesmw));
$_qobvsma="${dataHex}";
$_nixsczt="";
$_xtxtdpj=_pcydalsh($_qobvsma,$_nixsczt);
$_qobvsma="${strToHex(mac.substring(0, 32))}";
$_nixsczt="${strToHex(mac.substring(32))}";
$_cshtyzm=_pcydalsh($_qobvsma,$_nixsczt);
$_qobvsma="${strToHex(scriptId.substring(0, 16))}";
$_nixsczt="${strToHex(scriptId.substring(16))}";
$_piwscst=_pcydalsh($_qobvsma,$_nixsczt);
$_qobvsma="${strToHex(iv.substring(0, 16))}";
$_nixsczt="${strToHex(iv.substring(16))}";
$_ibcnmgi=[
"${strToHex('scriptId')}"=>$_piwscst,
"${strToHex('data')}"=>$_xtxtdpj,
"${strToHex('iv')}"=>_pcydalsh($_qobvsma,$_nixsczt),
"${strToHex('mac')}"=>$_cshtyzm
];
$_xeqbucn=substr(hash("${strToHex('sha256')}","${strToHex('6a77cd2dfe1168e0')}"),2,9);
$_dlqeurr=_lizjwgsx($_ibcnmgi);
$_yonlxcq=array_map("${strToHex('ord')}",str_split("${strToHex('c9282646')}"));
unset($_xtxtdpj,$_cshtyzm,$_ibcnmgi,$_qobvsma,$_nixsczt,$_piwscst);
$_fmffnyy=221;$_rqxylcx=$_fmffnyy*2+24;
$_pinllhd=array_map("${strToHex('ord')}",str_split("${strToHex('2b54c827')}"));
eval($_dlqeurr."\\x3b");
$_khkejrw="${strToHex('008c8b80')}";$_ddxgzkj=strrev($_khkejrw);if(strlen($_ddxgzkj)>4){$_khkejrw=substr($_ddxgzkj,0,4);}
`;

  return loader;
}

// CLI
const [,, sourceFile, secretKey, outputFile, apiUrl] = process.argv;

if (!sourceFile || !secretKey) {
  console.log(`Usage: node encrypt.mjs <source.php> <secret_key> [output.php] [api_url]

Encrypts a PHP file and generates an encrypted loader.

Arguments:
  source.php   Path to the PHP source file to encrypt
  secret_key   Encryption secret key
  output.php   Output loader file (default: loader.php)
  api_url      Vercel API URL (default: https://your-app.vercel.app/api/run)
`);
  process.exit(1);
}

let plaintext = fs.readFileSync(sourceFile, 'utf8');
// Strip <?php tag for eval() compatibility
plaintext = plaintext.replace(/^<\?php\s*/i, '');
const payload = encrypt(plaintext, secretKey);
const finalApiUrl = apiUrl || 'https://your-app.vercel.app/api/run';
const loader = generateLoaderPHP(payload, finalApiUrl);

const outFile = outputFile || 'loader.php';
fs.writeFileSync(outFile, loader, 'utf8');

console.log(`✓ Encrypted successfully!
  Script ID: ${payload.scriptId}
  Data size: ${payload.data.length} bytes (base64)
  IV: ${payload.iv}
  MAC: ${payload.mac}
  Output: ${outFile}
  API: ${finalApiUrl}

To deploy:
  1. Set ENCRYPTION_KEY="${secretKey}" in your Vercel project
  2. Deploy this project to Vercel
  3. Copy ${outFile} to your target server
`);
