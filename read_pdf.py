import sys
from PyPDF2 import PdfReader

def main():
    file_path = "C:/Users/gaizk/OneDrive/Documentos/GitHub/StatBet/Documentacion/StatBet.pdf"
    with open(file_path, "rb") as f:
        reader = PdfReader(f)
        for i, page in enumerate(reader.pages):
            text = page.extract_text()
            print(f"--- Page {i+1} ---")
            print(text)

if __name__ == "__main__":
    main()
