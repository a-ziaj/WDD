#!/bin/bash

PROTEIN="$1"
GROUP="$2"

echo "PATH: $PATH"
echo "PROTEIN: $PROTEIN"
echo "GROUP: $GROUP"

QUERY="${PROTEIN} AND ${GROUP}[Organism]"
echo "Query: $QUERY"

SAFE_NAME=$(echo "${PROTEIN}_${GROUP}" | tr ' /' '_')
FASTA_FILE="/tmp/${SAFE_NAME}_$(date +%s).fasta"

# 🛠️ Use FULL PATHS here!
/home/s2713107/edirect/esearch -db protein -query "$QUERY" \
  | /home/s2713107/edirect/efetch -format fasta > "$FASTA_FILE"

# Check if any sequences were retrieved
if [ ! -s "$FASTA_FILE" ]; then
  echo "No sequences found for query: ${PROTEIN} in ${GROUP}"
  exit 0
fi

NUM=$(grep -c "^>" "$FASTA_FILE")
echo "✅ Found $NUM sequences"
echo ""
head -n 60 "$FASTA_FILE"


