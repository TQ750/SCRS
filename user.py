import json
import os
import re
# JSON file to store learned data
data_file = 'learned_data.json'
 
# Load learned data from file (if exists)
def load_learned_data():
    if os.path.exists(data_file):
        try:
            with open(data_file, 'r') as file:
                return json.load(file)
        except json.JSONDecodeError:
            return {}
    return {}
 
# Save learned data to file
def save_learned_data(data):
    with open(data_file, 'w') as file:
        json.dump(data, file)
 
# Basic keyword extraction from a question
def extract_keywords(question):
    # Convert to lowercase, remove punctuation, and split into words
    words = re.findall(r'\b\w+\b', question.lower())
    return set(words)
 
# Check for keyword similarity between two questions
def keywords_match(question1, question2):
    keywords1 = extract_keywords(question1)
    keywords2 = extract_keywords(question2)
    return len(keywords1.intersection(keywords2)) > 0
 
# Main chatbot function
def chatbot():
    learned_data = load_learned_data()
 
    print("Chatbot: Hello! You can ask me questions. I will try to help you.")
 
    while True:
        user_input = input("You: ").strip().lower()
        if user_input in ["exit", "quit"]:
            print("Chatbot: Goodbye!")
            break
 
        # Try to find if there's any answered question already in learned data
        found_answer = False
        for learned_question, learned_answer in learned_data.items():
            if keywords_match(user_input, learned_question):
                print(f"Chatbot: {learned_answer}")
                found_answer = True
                break
        if not found_answer:
            print("Chatbot: 1 minute support employee will talk with you. Please wait!")
 
if __name__ == "__main__":
    chatbot()