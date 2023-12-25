# Issues

## Dockerfile VOLUME 레이어 추가 이후 발생 
```
node:internal/modules/cjs/loader:1146
  throw a mistake;
  ^
 
Error: Cannot find module '/app/server.js'.
    Module._resolveFilename (node:internal/modules/cjs/loader:1143:15)
    Module._load(node:internal/module/cjs/loader:984:27)
    Function.executeUserEntryPoint[as runMain] (node:internal/module/run_main:142:12)
    node: internal/main/run_main_module:28:49 {
  Code: 'MODULE_NOT_FOUND',
  Requirements Stack: []
}
 
Node.js v21.5.0
```
* 레이어 추가 후 이미지를 새로 빌드하고 시작했으나 위와 같은 이유로 detach 모드에서는 넘어간것처럼 보이지만 실제로 실행이 되자않는다, 옵션 제외하고 보니 저런 에러 뜨고있었음..

### Solution
* 찾는중
