import requests

def main():
    print("--- FurryNeeds Inventory | Python Client 2 ---")
    
    cat_id = input("Enter Category ID (1-3): ")
    max_price = input("Enter Maximum Budget: ")

    url = f"http://localhost/service.php?cat_id={cat_id}&price={max_price}"

    try:
        response = requests.get(url)

        data = response.json()

        print("\n--- Results ---")
        if isinstance(data, dict) and "message" in data:
            print(data["message"])
        else:
            for item in data:
   
                print(f"Product: {item['product_name']}")
                print(f"Price:   P{item['price']}")
                print(f"Status:  {item['status']}") 
                print("-" * 20)

    except Exception as e:

        print(f"Error: Could not connect to API. ({e})")

if __name__ == "__main__":
    main()
