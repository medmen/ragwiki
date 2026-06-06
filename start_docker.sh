docker build --network=host -t rag-inference-server .
docker run -d -p 8041:8041 --memory=8g rag-inference-server

## use
# docker container list
## to see running instance
# docker stop 728sfde8
## to stop container with id 728sfde8