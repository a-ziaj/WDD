#!/bin/bash

PROTEIN="$1"
GROUP="$2"
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
FASTA_FILE="$SCRIPT_DIR/tmp/${PROTEIN}_${GROUP}_sequences.fasta"

# Create tmp directory if it doesn't exist
mkdir -p "$SCRIPT_DIR/tmp"

# Create the NCBI query
QUERY="${PROTEIN} AND ${GROUP}[Organism]"
echo "Query: $QUERY" > "$FASTA_FILE.log"

# Run esearch/efetch
/home/s2713107/edirect/esearch -db protein -query "$QUERY" | /home/s2713107/edirect/efetch -format fasta > "$FASTA_FILE" 2>> "$FASTA_FILE.log"

# Check results
if [ ! -s "$FASTA_FILE" ]; then
  echo "No sequences found for query: ${PROTEIN} in ${GROUP}" >> "$FASTA_FILE.log"
  exit 1
fi

echo "Sequences saved to: $FASTA_FILE" >> "$FASTA_FILE.log"
