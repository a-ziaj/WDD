#!/bin/bash

PROTEIN="$1"
GROUP="$2"

# Define the FASTA output file path

FASTA_FILE="${PROTEIN}_${GROUP}_sequences.fasta"
FASTA_FILE="${FASTA_FILE// /_}" 

# Create the NCBI query
QUERY="${PROTEIN} AND ${GROUP}[Organism]"
echo "Query: $QUERY"

# 🛠️ Use FULL PATHS here!
/home/s2713107/edirect/esearch -db protein -query "$QUERY" | /home/s2713107/edirect/efetch -format fasta > "$FASTA_FILE"

# Check if any sequences were retrieved
if [ ! -s "$FASTA_FILE" ]; then
  echo "No sequences found for query: ${PROTEIN} in ${GROUP}"
  exit 1
fi

echo "Sequences saved to: $FASTA_FILE"

