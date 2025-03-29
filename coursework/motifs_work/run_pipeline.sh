#!/bin/bash

PROTEIN="$1"
GROUP="$2"
MAX_SEQUENCES="$3"

# Define the FASTA output file path
FASTA_FILE="${PROTEIN}_${GROUP}_sequences.fasta"

# Create the NCBI query with sequence limit
QUERY="${PROTEIN} AND ${GROUP}[Organism]"
echo "Query: $QUERY" > "$FASTA_FILE.log"
echo "Max sequences: $MAX_SEQUENCES" >> "$FASTA_FILE.log"

# Fetch sequences with limit
/home/s2713107/edirect/esearch -db protein -query "$QUERY" | \
/home/s2713107/edirect/efetch -format fasta -stop "$MAX_SEQUENCES" > "$FASTA_FILE" 2>> "$FASTA_FILE.log"

# Check if any sequences were retrieved
if [ ! -s "$FASTA_FILE" ]; then
  echo "No sequences found for query: ${PROTEIN} in ${GROUP}" >> "$FASTA_FILE.log"
  exit 1
fi

echo "Retrieved $(grep -c '^>' "$FASTA_FILE") sequences" >> "$FASTA_FILE.log"
exit 0
