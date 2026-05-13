from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from sentence_transformers import SentenceTransformer
import uvicorn
import torch

app = FastAPI()
models = {}

class EmbedRequest(BaseModel):
    texts: list[str]
    model: str = "all-MiniLM-L6-v2"

@app.post("/embed")
async def embed(req: EmbedRequest):
    try:
        # model_name = req.model if req.model in ["all-MiniLM-L6-v2", "paraphrase-multilingual-MiniLM-L12-v2"] else "all-MiniLM-L6-v2"
        model_name = "all-MiniLM-L6-v2"
        if model_name not in models:
            models[model_name] = SentenceTransformer(model_name, device='cpu')
        
        embeddings = models[model_name].encode(req.texts)
        return [emb.tolist() for emb in embeddings]
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    uvicorn.run(app, host="127.0.0.1", port=8041)
