docker build --network=host -t rag-embeddings .
docker run -d -p 8041:8041 --memory=8g rag-embeddings
