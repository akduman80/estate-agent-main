import {pdfEngine} from './pdf-output.js?v=documents-20261018-all1';
import {inspectPdf, allTextRemovals} from './pdf-edit.js?v=documents-20261018-all1';
import {replacePageText, discardUnreachableObjects} from './pdf-structure.js?v=documents-20261018-all1';

const encode = text => new TextEncoder().encode(text);
const xml = text => String(text).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&apos;'}[c]));

// A real OOXML ZIP, with editable text boxes and a text-free page background.
// No HTML disguised as DOCX, and no PDF text left hidden under replacement text.
export function zipFiles(files) {
    const chunks=[], central=[]; let offset=0;
    const crc = bytes => { let n=0xffffffff; for (const byte of bytes) {n^=byte;for(let j=0;j<8;j++)n=(n>>>1)^((n&1)?0xedb88320:0);}return (n^0xffffffff)>>>0; };
    const header=(size,values)=>{const b=new Uint8Array(size),v=new DataView(b.buffer);for(const [at,value,length] of values)length===2?v.setUint16(at,value,true):v.setUint32(at,value,true);return b;};
    for(const [name,contents] of Object.entries(files)) {
        const data=typeof contents==='string'?encode(contents):contents, filename=encode(name), checksum=crc(data);
        const local=header(30,[[0,0x04034b50,4],[4,20,2],[6,0x800,2],[10,0,2],[12,0x21,2],[14,checksum,4],[18,data.length,4],[22,data.length,4],[26,filename.length,2]]);
        const entry=header(46,[[0,0x02014b50,4],[4,20,2],[6,20,2],[8,0x800,2],[14,0x21,2],[16,checksum,4],[20,data.length,4],[24,data.length,4],[28,filename.length,2],[42,offset,4]]);
        chunks.push(local,filename,data);central.push(entry,filename);offset+=local.length+filename.length+data.length;
    }
    const centralSize=central.reduce((n,b)=>n+b.length,0),count=Object.keys(files).length;
    chunks.push(...central,header(22,[[0,0x06054b50,4],[8,count,2],[10,count,2],[12,centralSize,4],[16,offset,4]]));
    const result=new Uint8Array(chunks.reduce((n,b)=>n+b.length,0));let at=0;for(const b of chunks){result.set(b,at);at+=b.length;}return result;
}

export async function pdfToWord(bytes, pdfjs) {
    const lib=await pdfEngine();
    const task=pdfjs.getDocument({data:bytes.slice(),isEvalSupported:false,enableXfa:false,standardFontDataUrl:'/vendor/pdfjs/standard_fonts/'});
    let backgroundTask;
    try {
        const original=await task.promise,analyses=await inspectPdf(original,pdfjs.OPS);
        const pdf=await lib.PDFDocument.load(bytes.slice(),{updateMetadata:false});
        analyses.forEach((analysis,index)=>replacePageText(pdf,lib,index+1,analysis,allTextRemovals(analysis)));
        await discardUnreachableObjects(pdf,lib);
        backgroundTask=pdfjs.getDocument({data:await pdf.save(),isEvalSupported:false,standardFontDataUrl:'/vendor/pdfjs/standard_fonts/'});
        const backgrounds=await backgroundTask.promise;
        const files={},rels=[],body=[];
        for(let i=0;i<analyses.length;i++){
            const analysis=analyses[i],[width,height]=analysis.size;
            const page=await backgrounds.getPage(i+1),viewport=page.getViewport({scale:1.3});
            const canvas=document.createElement('canvas');canvas.width=Math.ceil(viewport.width);canvas.height=Math.ceil(viewport.height);
            await page.render({canvasContext:canvas.getContext('2d'),viewport}).promise;
            const blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/png'));if(!blob)throw new Error('Word sayfa arka planı oluşturulamadı.');
            files[`word/media/page${i+1}.png`]=new Uint8Array(await blob.arrayBuffer());canvas.width=canvas.height=0;
            rels.push(`<Relationship Id="bg${i}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/page${i+1}.png"/>`);
            let runs=`<w:r><w:pict><v:rect id="bg${i}" style="position:absolute;left:0;top:0;width:${width}pt;height:${height}pt;mso-position-horizontal-relative:page;mso-position-vertical-relative:page;z-index:-1" stroked="f"><v:imagedata r:id="bg${i}"/></v:rect></w:pict></w:r>`;
            for(const item of analysis.items){
                const family=/serif/i.test(item.fontFamily)&&!/sans/i.test(item.fontFamily)?'Times New Roman':'Arial';
                const angle=(item.angle||0)*Math.PI/180,boxHeight=item.fontSize*1.6;
                const centerX=item.x+Math.cos(angle)*(item.width+3)/2-Math.sin(angle)*item.fontSize*.12;
                const centerY=item.y+Math.sin(angle)*(item.width+3)/2+Math.cos(angle)*item.fontSize*.12;
                const left=centerX-(item.width+3)/2,top=height-centerY-boxHeight/2;
                runs+=`<w:r><w:pict><v:shape id="text${i}_${item.id}" type="#_x0000_t202" o:insetmode="custom" style="position:absolute;left:${left}pt;top:${top}pt;rotation:${-(item.angle||0)};width:${item.width+3}pt;height:${boxHeight}pt;mso-wrap-style:none;v-text-anchor:top;mso-position-horizontal-relative:page;mso-position-vertical-relative:page" stroked="f" filled="f"><v:textbox style="mso-fit-shape-to-text:t" inset="0,0,0,0"><w:txbxContent><w:p><w:pPr><w:spacing before="0" after="0" line="${Math.round(item.fontSize*22)}" lineRule="exact"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="${family}" w:hAnsi="${family}"/><w:sz w:val="${Math.round(item.fontSize*2)}"/>${/bold/i.test(item.fontFamily)?'<w:b/>':''}${/italic/i.test(item.fontFamily)?'<w:i/>':''}</w:rPr><w:t xml:space="preserve">${xml(item.text)}</w:t></w:r></w:p></w:txbxContent></v:textbox></v:shape></w:pict></w:r>`;
            }
            const section=`<w:sectPr><w:type w:val="nextPage"/><w:pgSz w:w="${Math.round(width*20)}" w:h="${Math.round(height*20)}"/><w:pgMar w:top="0" w:right="0" w:bottom="0" w:left="0" w:header="0" w:footer="0"/></w:sectPr>`;
            body.push(`<w:p><w:pPr><w:spacing after="0" line="20" lineRule="exact"/>${i<analyses.length-1?section:''}</w:pPr>${runs}</w:p>${i===analyses.length-1?section:''}`);
        }
        files['[Content_Types].xml']='<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>';
        files['_rels/.rels']='<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="document" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>';
        files['word/_rels/document.xml.rels']='<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'+rels.join('')+'</Relationships>';
        files['word/document.xml']='<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"><w:body><w:p><w:pPr><w:spacing after="0" line="1" lineRule="exact"/></w:pPr><w:r><w:pict><v:shapetype id="_x0000_t202" coordsize="21600,21600" o:spt="202" path="m,l,21600r21600,l21600,xe"><v:stroke joinstyle="miter"/><v:path gradientshapeok="t" o:connecttype="rect"/></v:shapetype></w:pict></w:r></w:p>'+body.join('')+'</w:body></w:document>';
        return zipFiles(files);
    }finally{await backgroundTask?.destroy();await task.destroy();}
}
