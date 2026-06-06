FROM python:3.11-slim

RUN pip install fastapi uvicorn
RUN pip install torch torchaudio --index-url https://download.pytorch.org/whl/cpu
RUN pip install sentence-transformers transformers accelerate

COPY app.py .

EXPOSE 8041
CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8041"]
