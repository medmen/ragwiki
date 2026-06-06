from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from sentence_transformers import SentenceTransformer
from transformers import AutoModelForCausalLM, AutoTokenizer, pipeline
import uvicorn
import torch

app = FastAPI()
models = {}

class EmbedRequest(BaseModel):
    texts: list[str]
    model: str = "all-MiniLM-L6-v2"

class GenerateRequest(BaseModel):
    prompt: str
    max_tokens: int = 512
    temperature: float = 0.1

@app.post("/embed")
async def embed(req: EmbedRequest):
    try:
        model_name = "all-MiniLM-L6-v2"
        if model_name not in models:
            models[model_name] = SentenceTransformer(model_name, device='cpu')
        
        embeddings = models[model_name].encode(req.texts)
        return [emb.tolist() for emb in embeddings]
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/generate")
async def generate(req: GenerateRequest):
    try:
        model_name = "HuggingFaceTB/SmolLM2-135M-Instruct"
        if model_name not in models:
            tokenizer = AutoTokenizer.from_pretrained(model_name)
            model = AutoModelForCausalLM.from_pretrained(model_name)
            models[model_name] = pipeline("text-generation", model=model, tokenizer=tokenizer, device=-1) # -1 for CPU
        
        pipe = models[model_name]
        # Using a simple chat template or just the prompt
        messages = [{"role": "user", "content": req.prompt}]
        result = pipe(messages, max_new_tokens=req.max_tokens, temperature=req.temperature, do_sample=True)
        
        answer = result[0]['generated_text'][-1]['content']
        return {"answer": answer}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8041)
