import openai
import json
import os
import re
from collections import Counter
import wikipediaapi  # Add Wikipedia API

# Set your OpenAI API key here
openai.api_key = 'sk-svcacct-D0C-6hYOxpZsZfnrJH1Pwv5DWN1WuhdBMjpsIPcWokgVhiv-s5ylNnOQIOBP1ueT3BlbkFJM1p-H_fZLXtMiBnoKdRNpaV63otVkVKVgUa74yKwVVcEw6-K2pIkKVYeQMC4dAA'

# JSON file to store learned data
data_file = 'learned_data.json'

# Wikipedia API instance with a valid user-agent
wiki_wiki = wikipediaapi.Wikipedia(
    language='en', 
    user_agent='MyChatBot/1.0 (https://example.com/; myemail@example.com)'  # Replace with your details
)

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

# Ask OpenAI API for an answer
def ask_openai(question):
    try:
        response = openai.ChatCompletion.create(
            model="gpt-3.5-turbo",  # Updated model name
            messages=[{"role": "user", "content": question}],
            max_tokens=150,
            n=1,
            stop=None,
            temperature=0.7,
        )
        answer = response.choices[0].message['content'].strip()
        return answer
    except Exception as e:
        return f"Error: {str(e)}"

# Search Wikipedia if OpenAI can't provide a valid answer
def search_wikipedia(question):
    page = wiki_wiki.page(question)
    if page.exists():
        return page.summary[:500]  # Return first 500 characters of the summary
    else:
        return "I couldn't find an answer to your question on Wikipedia."

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

    print("Chatbot: Hello! You can ask me questions. If I don't know, you can teach me.")
    while True:
        user_input = input("You: ").strip().lower()
        if user_input in ["exit", "quit"]:
            print("Chatbot: Goodbye!")
            break

        # Try to find if there's any semantically similar question already answered
        found_similar = False
        for learned_question, learned_answer in learned_data.items():
            if keywords_match(user_input, learned_question):
                print(f"Chatbot (learned from similar question): {learned_answer}")
                found_similar = True
                break
        if not found_similar:
            # Ask OpenAI for an answer if no similar question was found
            answer = ask_openai(user_input)
            if "Error" in answer or len(answer) == 0:
                # If OpenAI couldn't answer or returns an error, search Wikipedia
                answer = search_wikipedia(user_input)

            print(f"Chatbot: {answer}")

            # Ask if the answer was correct and learn if necessary
            feedback = input("Was my answer correct? (yes/no/teach): ").strip().lower()
            if feedback == "no" or feedback == "teach":
                correct_answer = input("What is the correct answer? ")
                learned_data[user_input] = correct_answer
                save_learned_data(learned_data)
                print("Chatbot: Thank you for teaching me!")
            elif feedback == "yes":
                print("Chatbot: Great!")

if __name__ == "__main__":
    chatbot()
