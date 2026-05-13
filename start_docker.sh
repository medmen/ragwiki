docker build --network=host -t rag-embeddings .
docker run -d -p 8041:8041 --memory=8g rag-embeddings

## use
# docker container list
## to see running instance
# docker stop 728sfde8
## to stop container with id 728sfde8